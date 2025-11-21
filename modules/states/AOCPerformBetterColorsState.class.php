<?php
/**
 * Backend functions used by the performBetterColors State
 */
class AOCPerformBetterColorsState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        return [];
    }

    /**
     * Logic to apply the Better Color Token
     * This is called when the player clicks the "Collect" button
     */
    public function gainBetterColor() {
        $player = $this->game->playerManager->getActivePlayer();
        $playerId = $player->getId();

        // 1. Find the most recently printed comic for this player
        // We sort by ID descending to get the newest one (the one just printed)
        $sql = "SELECT * FROM mini_comic WHERE mini_comic_owner = $playerId ORDER BY mini_comic_id DESC LIMIT 1";
        $comic = $this->game->getObjectFromDB($sql);

        if (!$comic) {
            // Should not happen if logic is correct, but safety first:
            $this->game->gamestate->nextState("checkUpgrade");
            return;
        }

        $comicId = $comic['mini_comic_id'];

        // 2. Mark the Comic in the Database
        // We need a way to flag "Has Better Color". 
        // We will add +100 to the 'type_arg' column. 
        // (Assuming type_arg is usually < 100. This acts as a "flag" we can check during scoring).
        $this->game->DbQuery("UPDATE mini_comic SET mini_comic_type_arg = mini_comic_type_arg + 100 WHERE mini_comic_id = $comicId");

        // 3. Notify the Frontend
        // This triggers the JS we wrote earlier to display the 'better_color.png' token
        $this->game->notifyAllPlayers(
            "gainBetterColorToken",
            clienttranslate('${player_name} adds Better Colors to the print run! (+2 VP at end of game)'),
            [
                'player_name' => $player->getName(),
                'player' => $player->getUiData(),
                'miniComicId' => $comicId
            ]
        );

        // 4. Move to the next step (The Upgrade Cube)
        $this->game->gamestate->nextState("checkUpgrade");
    }
}
