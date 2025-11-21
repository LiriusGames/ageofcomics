<?php
class AOCRoundEndRemoveEditorsState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        return [];
    }

    public function stRoundEndRemoveEditors() {
        $players = $this->game->playerManager->getPlayers();

        // 1. Return ALL editors from Board -> Player Area
        // (We do this first so all meeples are in one place for counting)
        foreach ($players as $player) {
            $playerId = $player->getId();
            
            // Get editors on the board (not in supply/player area)
            $sql = "SELECT * FROM editor WHERE editor_owner = $playerId AND editor_location != " . LOCATION_PLAYER_AREA;
            $editorsOnBoard = $this->game->getCollectionFromDB($sql);

            foreach ($editorsOnBoard as $editor) {
                // Move back to player area DB
                $this->game->DbQuery("UPDATE editor SET editor_location = " . LOCATION_PLAYER_AREA . " WHERE editor_id = " . $editor['editor_id']);
                
                // Notify UI
                $this->game->notifyAllPlayers("moveEditorToPlayerArea", "", [
                    "editor" => $editor,
                    "player" => $player->getUiData(),
                ]);
            }
        }

        // 2. IDENTIFY AND REMOVE THE EXTRA EDITOR
        // Now that everyone has their meeples back, check who has 5.
        foreach ($players as $player) {
            $playerId = $player->getId();
            
            // Count how many editors this player currently 'owns'
            $allEditors = $this->game->getCollectionFromDB("SELECT * FROM editor WHERE editor_owner = $playerId");
            
            if (count($allEditors) > 4) {
                // They have 5! We need to find the impostor.
                $extraEditor = null;

                // Strategy A: Check Color (If extra editor is different color)
                // Assuming player has a specific color in DB (e.g. 'ff0000')
                $playerColor = $player->getColor();
                foreach($allEditors as $ed) {
                    if ($ed['editor_color'] != $playerColor) {
                        $extraEditor = $ed;
                        break;
                    }
                }

                // Strategy B: Fallback (If colors match, take the highest ID)
                // The Extra Editor was likely created last in setup
                if ($extraEditor == null) {
                    $extraEditor = end($allEditors); // Last one in the array
                }

                // 3. Reset the Extra Editor
                // Remove ownership (Set to 0) and move to Extra Space
                $eId = $extraEditor['editor_id'];
                $this->game->DbQuery("UPDATE editor SET editor_owner = 0, editor_location = " . LOCATION_EXTRA_EDITOR . " WHERE editor_id = $eId");

                // Notify UI
                $this->game->notifyAllPlayers("moveEditorToExtraEditorSpace", clienttranslate("The Extra Editor returns to the supply."), [
                    "editor" => $extraEditor
                ]);
            }
        }

        $this->game->gamestate->nextState("refillCards");
    }
}
