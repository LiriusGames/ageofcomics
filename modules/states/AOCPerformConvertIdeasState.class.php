<?php
/**
 * Backend functions used by the performConvertIdeas State
 */
class AOCPerformConvertIdeasState {
    private $game;

    public function __construct($game) {
        $this->game = $game;
    }

    public function getArgs($playerId = null) {
        return [];
    }

    public function convertIdeas($ideasToConvert, $targetComicIds) {
        $player = $this->game->playerManager->getActivePlayer();
        $playerId = $player->getId();

        // 1. Validation
        if (count($ideasToConvert) != count($targetComicIds)) {
            throw new BgaUserException("Mismatch between ideas and target comics.");
        }
        if (count($ideasToConvert) > 3) {
            throw new BgaUserException("You can convert a maximum of 3 ideas.");
        }
        
        // Rule: "Not a single comic book by 3 fans" (Max 1 per comic per action)
        if (count($targetComicIds) !== count(array_unique($targetComicIds))) {
            throw new BgaUserException("You cannot boost the same comic twice in one action.");
        }

        // 2. Process each conversion
        for ($i = 0; $i < count($ideasToConvert); $i++) {
            $genreId = $ideasToConvert[$i];
            $comicId = $targetComicIds[$i];

            // Check if player has the idea
            // We need to map Genre ID (int) to Column Name (string)
            // e.g. 1 -> 'crime'
            $genreName = $this->game->getGenreName($genreId); // Ensure this helper exists in game.php
            $column = "player_" . $genreName . "_ideas";
            
            // Verify ownership
            $currentIdeas = $this->game->getUniqueValueFromDB("SELECT $column FROM player WHERE player_id = $playerId");
            if ($currentIdeas < 1) {
                throw new BgaUserException("You do not have enough $genreName ideas.");
            }

            // Deduct Idea
            $this->game->playerManager->adjustIdeas($playerId, $genreName, -1);

            // Add Fan to Comic
            $this->game->DbQuery("UPDATE mini_comic SET mini_comic_fans = mini_comic_fans + 1 WHERE mini_comic_id = $comicId");
        }

        // 3. Notifications
        $this->game->notifyAllPlayers(
            "adjustIdeas",
            clienttranslate('${player_name} converts ideas into fans!'),
            [
                'player_name' => $player->getName(),
                'player' => $player->getUiData(),
                // We send a generic update, frontend will sync counters
                'genre' => 'mixed', 
                'numOfIdeas' => -count($ideasToConvert)
            ]
        );

        // Notify the fan increase (using moveMiniComic to update chart position)
        // We need to fetch updated comics to animate them
        foreach ($targetComicIds as $cId) {
            $updatedComic = $this->game->getObjectFromDB("SELECT * FROM mini_comic WHERE mini_comic_id = $cId");
            $this->game->notifyAllPlayers(
                "moveMiniComic",
                "",
                [
                    'player' => $player->getUiData(),
                    'miniComic' => $updatedComic,
                    'fansChange' => 1,
                    'incomeChange' => 0
                ]
            );
        }

        // 4. End State
        $this->game->gamestate->nextState("nextPlayerTurn");
    }

    public function skipConvertIdeas() {
        $this->game->gamestate->nextState("nextPlayerTurn");
    }
}
