<?php
/**
 * Backend functions used by the roundEndPayEarnings State
 */
class AOCRoundEndPayEarningsState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        return [];
    }

    public function stRoundEndPayEarnings() {
        // 1. Standard Round Earnings (Happens every round, including the last)
        $players = $this->game->playerManager->getPlayers();
        
        // Sort in ascending turn order for nice UI notification sequence
        usort($players, function ($a, $b) {
            return $a->getTurnOrder() - $b->getTurnOrder();
        });

        foreach ($players as $player) {
            $income = $player->getIncome();
            $this->game->playerManager->adjustPlayerMoney($player, $income);

            $this->game->notifyAllPlayers(
                "adjustMoney",
                clienttranslate('${player_name} earns $${amount} from printed comics.'),
                [
                    "player_name" => $player->getName(),
                    "player" => $player->getUiData(),
                    "amount" => $income,
                ]
            );
        }

        // 2. CHECK GAME END
        if ($this->game->getGameStateValue(CURRENT_ROUND) === 5) {
            // STOP! Calculate Final Scores before finishing.
            $this->calculateFinalScoring();
            $this->game->gamestate->nextState("endGame");
        } else {
            $this->game->gamestate->nextState("establishPlayerOrder");
        }
    }

    /**
     * Calculates all End Game Victory Points
     */
    private function calculateFinalScoring() {
        $players = $this->game->playerManager->getPlayers();

        foreach ($players as $player) {
            $playerId = $player->getId();
            $vpLog = []; // To log details in chat if needed
            $totalVP = 0;

            // A. FANS (1 VP per Fan)
            $comics = $this->game->miniComicManager->getMiniComicsByPlayer($playerId);
            $fanVP = 0;
            foreach ($comics as $comic) {
                $fanVP += $comic['fans'];
                
                // B. BETTER COLORS (2 VP)
                // We flagged this by adding 100 to the type_arg
                if ($comic['type_arg'] >= 100) {
                    $totalVP += 2;
                    $this->notifyScore($player, 2, "Better Colors Bonus");
                }
            }
            $totalVP += $fanVP;
            // We assume fan points are tracked live on the score track. 
            // If BGA score track only tracks VP tokens, we add FanVP here. 
            // Assuming fans = score on track, we don't add it again, 
            // BUT usually fans are distinct from VP until the end. 
            // Let's assume we ADD these points now.
            $this->notifyScore($player, $fanVP, "Final Fan Count");


            // C. MONEY (1 VP per $4)
            $money = $player->getMoney();
            $moneyVP = floor($money / 4);
            if ($moneyVP > 0) {
                $totalVP += $moneyVP;
                $this->notifyScore($player, $moneyVP, "Cash Conversion ($4 = 1 VP)");
            }

            // D. IDEAS (1 VP per 4 Ideas)
            // We need to sum all idea counters
            // We use a raw SQL query to be safe and fast
            $pRow = $this->game->getObjectFromDB("SELECT * FROM player WHERE player_id = $playerId");
            $totalIdeas = $pRow['player_crime_ideas'] + $pRow['player_horror_ideas'] + 
                          $pRow['player_romance_ideas'] + $pRow['player_scifi_ideas'] + 
                          $pRow['player_superhero_ideas'] + $pRow['player_western_ideas'];
            
            $ideaVP = floor($totalIdeas / 4);
            if ($ideaVP > 0) {
                $totalVP += $ideaVP;
                $this->notifyScore($player, $ideaVP, "Idea Conversion (4 Ideas = 1 VP)");
            }

            // E. MASTERY TOKENS (2 VP Each)
            $masteryCount = $this->game->getUniqueValueFromDB("SELECT count(*) FROM mastery_token WHERE mastery_token_owner = $playerId");
            if ($masteryCount > 0) {
                $masteryVP = $masteryCount * 2;
                $totalVP += $masteryVP;
                $this->notifyScore($player, $masteryVP, "Mastery Tokens");
            }

            // F. ORIGINALS BONUS (Matching Creatives)
            // 2 VP (0 matches), 4 VP (1 match), 6 VP (2 matches)
            // We need to iterate through the player's slots on the mat
            $slots = [1, 2, 3, 4, 5, 6]; // Max slots
            foreach ($slots as $slot) {
                $cards = $this->game->cardManager->getCardsInLocation('player_mat', $playerId, $slot);
                
                $comic = null;
                $writer = null;
                $artist = null;

                foreach($cards as $c) {
                    if ($c['type'] == 'comic') $comic = $c; // Only ORIGINALS, not Rip-offs
                    if ($c['type'] == 'writer') $writer = $c;
                    if ($c['type'] == 'artist') $artist = $c;
                }

                if ($comic && $writer && $artist) {
                    $matches = 0;
                    // Check Genre Match (Assuming 'genre' column holds the genre ID)
                    if ($comic['genre'] == $writer['genre']) $matches++;
                    if ($comic['genre'] == $artist['genre']) $matches++;

                    $bonus = 2; // Default
                    if ($matches == 1) $bonus = 4;
                    if ($matches == 2) $bonus = 6;

                    $totalVP += $bonus;
                    $this->notifyScore($player, $bonus, "Original Comic Bonus (Slot $slot)");
                }
            }
            
            // Update the actual score in DB
            $this->game->playerManager->adjustPlayerScore($player, $totalVP);
        }
    }

    // Helper to send a notification for clarity
    private function notifyScore($player, $points, $reason) {
        $this->game->notifyAllPlayers(
            "message", 
            clienttranslate('${player_name} gains ${points} VP: ${reason}'), 
            [
                'player_name' => $player->getName(),
                'points' => $points,
                'reason' => $reason
            ]
        );
    }
}
