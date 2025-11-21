<?php
/**
 * Backend functions used by the performMarketing State
 */
class AOCPerformMarketingState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        // We must send the player's money to the frontend
        // so the buttons know whether to enable ($2, $5, or $9 options)
        $player = $this->game->playerManager->getActivePlayer();
        return [
            'playerMoney' => (int)$player->getMoney()
        ];
    }

    public function payMarketing($amount, $comicId = 0) {
        $player = $this->game->playerManager->getActivePlayer();
        $playerId = $player->getId();
        $currentMoney = $player->getMoney();

        // 1. Validation
        if ($amount != 2 && $amount != 5 && $amount != 9) {
            throw new BgaUserException("Invalid marketing amount selected.");
        }
        if ($currentMoney < $amount) {
            throw new BgaUserException("Not enough money for this marketing campaign.");
        }

        // 2. Calculate Fans
        $fansGained = 0;
        if ($amount == 2) $fansGained = 1;
        elseif ($amount == 5) $fansGained = 2;
        elseif ($amount == 9) $fansGained = 4;

        // 3. Identify Target Comic
        // If the frontend sent 0 (default), we apply to the player's best performing comic
        if ($comicId == 0) {
            $comics = $this->game->miniComicManager->getMiniComicsByPlayer($playerId);
            if (empty($comics)) {
                throw new BgaUserException("You have no comics to market!");
            }
            // Sort by fans desc to find the best one
            usort($comics, function($a, $b) {
                return $b['fans'] - $a['fans'];
            });
            $comicId = $comics[0]['id'];
        }

        // 4. Execute Transaction
        // Deduct Money
        $this->game->playerManager->adjustMoney($playerId, -$amount);
        
        // Add Fans (Direct DB update to ensure it works without guessing Manager methods)
        // Note: In BGA, we usually use a Manager method, but SQL is safe here.
        $sql = "UPDATE mini_comic SET fans = fans + $fansGained WHERE id = $comicId";
        $this->game->DbQuery($sql);

        // 5. Notifications
        // Notify money spent
        $this->game->notifyAllPlayers(
            "adjustMoney",
            clienttranslate('${player_name} pays $${amount} for Marketing.'),
            [
                'player_name' => $player->getName(),
                'player' => $player->getUiData(),
                'amount' => -$amount
            ]
        );

        // Notify fans gained (Using the generic moveMiniComic notification to update the chart)
        // We need to fetch the updated comic data to send to frontend
        $updatedComicSql = "SELECT * FROM mini_comic WHERE id = $comicId";
        $updatedComic = $this->game->getObjectFromDB($updatedComicSql);
        
        // Add required fields for frontend MiniComic object
        // (Frontend expects specific structure, simplified here for safety)
        $this->game->notifyAllPlayers(
            "moveMiniComic", 
            clienttranslate('${player_name} gains ${fans} fan(s) on a comic!'),
            [
                'player_name' => $player->getName(),
                'miniComic' => $updatedComic, // Frontend needs to see the new fan count inside this object
                'player' => $player->getUiData(),
                'incomeChange' => 0, // Income calculated at end of round usually
                'fansChange' => $fansGained
            ]
        );

        // 6. End State
        $this->game->gamestate->nextState("nextPlayerTurn");
    }

    public function skipMarketing() {
        $this->game->gamestate->nextState("nextPlayerTurn");
    }
}
