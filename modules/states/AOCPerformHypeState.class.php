<?php
/**
 * Backend functions used by the performHype State
 */
class AOCPerformHypeState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        return [];
    }

    public function hypeComic($cardId) {
        $playerId = $this->game->getPlayerId();
        
        // 1. Security Check: Does the card belong to the player?
        $card = $this->game->cardManager->getCard($cardId);
        if ($card['location'] != 'hand' || $card['player_id'] != $playerId) {
            throw new BgaUserException("You can only hype cards from your hand.");
        }

        // 2. Security Check: Is it a Comic?
        // Assuming type_id 3 is Comic and 4 is Ripoff (based on standard BGA logic)
        // You might need to check CONSTANTS if this fails, but usually type is string 'comic'
        if ($card['type'] != 'comic' && $card['type'] != 'ripoff') {
             throw new BgaUserException("You can only hype Comic Books.");
        }

        // 3. Move the Card
        // We move it to a special location so it doesn't count towards Hand Limit
        // location_arg 2 represents "2 Fans" (The Hype Token value)
        $this->game->cardManager->moveCard($cardId, 'hyped', $playerId);
        
        // 4. Add the "Hype Token" (Visuals handled by Notification, Data by location_arg)
        // We use a database update to ensure the token value (2) is saved
        // If your cardManager has a specific function for tokens, use that, otherwise:
        $sql = "UPDATE card SET card_location_arg = 2 WHERE card_id = $cardId";
        $this->game->DbQuery($sql);

        // 5. Notify Players
        $this->game->notifyAllPlayers(
            "hypeComic",
            clienttranslate('${player_name} hypes a comic! It gains a Hype Token (2 Fans).'),
            [
                'player_name' => $this->game->getActivePlayerName(),
                'card' => $card,
                'cardId' => $cardId
            ]
        );

        // 6. End State
        $this->game->gamestate->nextState("nextPlayerTurn");
    }

    public function skipHype() {
        // Just move to the next state
        $this->game->gamestate->nextState("nextPlayerTurn");
    }
}
