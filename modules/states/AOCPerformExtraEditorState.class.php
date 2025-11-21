<?php
class AOCPerformExtraEditorState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        return [];
    }

    public function gainExtraEditor() {
        $player = $this->game->playerManager->getActivePlayer();
        $playerId = $player->getId();

        // 1. Find the meeple currently sitting on the Extra Editor Space
        // We use the specific column names from your dbmodel.sql
        $sql = "SELECT * FROM editor WHERE editor_location = " . LOCATION_EXTRA_EDITOR . " LIMIT 1";
        $editor = $this->game->getObjectFromDB($sql);

        if (!$editor) {
            // Safety: If already taken, just move on
            $this->game->gamestate->nextState("nextPlayerTurn");
            return;
        }

        // 2. Assign it to the player (Change Owner and Location)
        // We temporarily make the player the 'owner' so the game lets them use it
        $editorId = $editor['editor_id'];
        $updateSql = "UPDATE editor SET editor_owner = $playerId, editor_location = " . LOCATION_PLAYER_AREA . " WHERE editor_id = $editorId";
        $this->game->DbQuery($updateSql);

        // Update the PHP object for the notification
        $editor['editor_owner'] = $playerId;
        $editor['editor_location'] = LOCATION_PLAYER_AREA;

        // 3. Notify
        $this->game->notifyAllPlayers(
            "moveEditorToPlayerArea",
            clienttranslate('${player_name} gains the Extra Editor for this round!'),
            [
                'player_name' => $player->getName(),
                'editor' => $editor, // Frontend needs this to know WHICH meeple ID to move
                'player' => $player->getUiData()
            ]
        );

        $this->game->gamestate->nextState("nextPlayerTurn");
    }

    public function skipExtraEditor() {
        $this->game->gamestate->nextState("nextPlayerTurn");
    }
}
