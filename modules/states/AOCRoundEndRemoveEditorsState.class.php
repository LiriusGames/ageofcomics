<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * AgeOfComics implementation : © Evan Pulgino <evan.pulgino@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Backend functions used by the roundEndRemoveEditors State
 *
 * This state handles the remove editors fans step of the end of round phase.
 *
 * @EvanPulgino
 */

class AOCRoundEndRemoveEditorsState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        return [];
    }

    /**
     * Remove all editors from the board and return them to their owners
     */
    public function stRoundEndRemoveEditors() {
        // 1. FIRST: Force the "Extra Editor" back to its spot on the board.
        // We do this via SQL to ensure we catch it whether it is on the board OR in a player's hand.
        // We assume the extra editor has a distinct type (e.g. 'extra_editor' or type_id 2).
        // If your database uses a specific ID for it, this query handles it.
        
        // Find the extra editor to animate it (UI polish)
        $extraEditor = $this->game->getObjectFromDB("SELECT * FROM editor WHERE type = 'extra_editor' LIMIT 1");
        
        if ($extraEditor && $extraEditor['location_id'] != LOCATION_EXTRA_EDITOR) {
            // Move it in DB
            $this->game->DbQuery("UPDATE editor SET location_id = '" . LOCATION_EXTRA_EDITOR . "', player_id = NULL WHERE type = 'extra_editor'");
            
            // Notify frontend to slide it back
            $this->game->notifyAllPlayers(
                "moveEditorToExtraEditorSpace",
                clienttranslate('The Extra Editor returns to the supply.'),
                [
                    "editor" => $extraEditor
                ]
            );
        }

        // 2. SECOND: Return all standard editors to their owners.
        $players = $this->game->playerManager->getPlayers();

        foreach ($players as $player) {
            // Get all standard editors (Not extra) that are NOT in the player area
            // We check for editors that are currently "on board" (placed on action spaces)
            $editors = $this->game->getCollectionFromDB(
                "SELECT * FROM editor WHERE player_id = " . $player->getId() . " AND location_id != '" . LOCATION_PLAYER_AREA . "' AND type != 'extra_editor'"
            );

            foreach ($editors as $editor) {
                // Move editor back to player area
                $this->game->editorManager->moveEditor(
                    $editor['id'],
                    LOCATION_PLAYER_AREA
                );

                // Notify frontend
                $this->game->notifyAllPlayers(
                    "moveEditorToPlayerArea",
                    "",
                    [
                        "editor" => $editor,
                        "player" => $player->getUiData(),
                    ]
                );
            }
        }

        // Go to next state
        $this->game->gamestate->nextState("refillCards");
    }
}
