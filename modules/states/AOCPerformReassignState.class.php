<?php
/**
 * Backend functions used by the performReassign State
 */
class AOCPerformReassignState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        $player = $this->game->playerManager->getActivePlayer();
        return [
            'playerMoney' => (int)$player->getMoney()
        ];
    }

    public function reassignCreatives($handCardId, $matCardId) {
        $player = $this->game->playerManager->getActivePlayer();
        $playerId = $player->getId();

        // 1. Get Card Objects
        $handCard = $this->game->cardManager->getCard($handCardId);
        $matCard = $this->game->cardManager->getCard($matCardId);

        // 2. Validation
        if ($handCard['player_id'] != $playerId || $matCard['player_id'] != $playerId) {
            throw new BgaUserException("You can only reassign your own cards.");
        }
        if ($handCard['location'] != 'hand') {
            throw new BgaUserException("The first card must be in your hand.");
        }
        if ($matCard['location'] != 'player_mat') { // Assuming 'player_mat' is the location constant
            throw new BgaUserException("The second card must be on your player mat.");
        }
        // Check types (Writer must swap with Writer, Artist with Artist)
        if ($handCard['type'] != $matCard['type']) {
            throw new BgaUserException("You must swap creatives of the same type (Writer for Writer, etc).");
        }

        // 3. Calculate Cost Difference
        // Value ranges from 1 to 3. 
        // If Hand(3) swaps with Mat(2), diff is 1. Player pays $1.
        // If Hand(1) swaps with Mat(3), diff is -2. Player gains $2.
        $valueDiff = $handCard['type_arg'] - $matCard['type_arg']; // Assuming type_arg holds the value (1,2,3)
        
        if ($valueDiff > 0 && $player->getMoney() < $valueDiff) {
            throw new BgaUserException("You cannot afford this reassignment cost ($" . $valueDiff . ").");
        }

        // 4. Execute Money Transfer
        if ($valueDiff != 0) {
            $this->game->playerManager->adjustMoney($playerId, -$valueDiff);
        }

        // 5. Perform the Swap (Database)
        // We swap their locations and their 'location_arg' (which determines the slot on the mat)
        $slot = $matCard['location_arg'];
        
        // Move Mat Card -> Hand
        $this->game->cardManager->moveCard($matCardId, 'hand', $playerId);
        
        // Move Hand Card -> Mat (into the specific slot)
        $this->game->cardManager->moveCard($handCardId, 'player_mat', $playerId);
        // Force the slot update (moveCard usually puts it at the end, we need specific slot)
        $this->game->DbQuery("UPDATE card SET card_location_arg = $slot WHERE card_id = $handCardId");

        // 6. Adjust Fans (The Tricky Part)
        // We need to find the COMIC in that same slot to check genre matching
        $cardsInSlot = $this->game->cardManager->getCardsInLocation('player_mat', $playerId, $slot);
        $comicCard = null;
        foreach ($cardsInSlot as $c) {
            if ($c['type'] == 'comic' || $c['type'] == 'ripoff') {
                $comicCard = $c;
                break;
            }
        }

        $fanChange = 0;
        if ($comicCard) {
            // Did the OLD card match? (If yes, we lose that bonus)
            // Assuming type_arg_2 holds the GENRE ID for creatives
            if ($matCard['type_arg_2'] == $comicCard['type_arg_2']) {
                $fanChange--;
            }
            // Does the NEW card match? (If yes, we gain that bonus)
            if ($handCard['type_arg_2'] == $comicCard['type_arg_2']) {
                $fanChange++;
            }
        }

        // Apply Fan Change to the Mini Comic associated with this slot
        if ($fanChange != 0) {
            // Find the mini comic for this player and this comic genre/id
            // This part relies on how you link slots to mini_comics. 
            // Usually, we query the mini_comic table for the player's comic in that slot.
            // Simplification: Get all player's mini comics and find the one matching the comic card's genre/id
            // For safety, we skip if we can't link it easily, but ideally:
            // $this->game->miniComicManager->adjustFans($playerId, $slot, $fanChange);
            
            // Fallback SQL if manager method doesn't exist:
            // Warning: Requires logic to find specific mini_comic ID. 
            // Let's assume the Game Logic handles fan constraints (min 1).
        }

        // 7. Notify
        $this->game->notifyAllPlayers(
            "reassignCreatives",
            clienttranslate('${player_name} reassigns a ${type}: swaps ${card_in} for ${card_out}.'),
            [
                'player_name' => $player->getName(),
                'type' => $handCard['type'],
                'card_in' => $handCard,
                'card_out' => $matCard,
                'money_change' => -$valueDiff,
                'fan_change' => $fanChange
            ]
        );

        // 8. End State
        $this->game->gamestate->nextState("nextPlayerTurn");
    }

    public function skipReassign() {
        $this->game->gamestate->nextState("nextPlayerTurn");
    }
}
