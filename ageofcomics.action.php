<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * AgeOfComics implementation : © Evan Pulgino <evan.pulgino@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * ageofcomics.action.php
 *
 * AgeOfComics main action entry point
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/ageofcomics/ageofcomics/myAction.html", ...)
 *
 * @link https://en.doc.boardgamearena.com/Players_actions:_yourgamename.action.php
 *
 * @EvanPulgino
 */

class action_ageofcomics extends APP_GameAction {
    // Constructor: please do not modify
    public function __default() {
        if (self::isArg("notifwindow")) {
            $this->view = "common_notifwindow";
            $this->viewArgs["table"] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "ageofcomics_ageofcomics";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function collectSalesOrder() {
        self::setAjaxMode();

        $salesOrderId = self::getArg("salesOrderId", AT_posint, true);
        $playerIdToPay = self::getArg("playerIdToPay", AT_posint, true);

        $this->game->states[PERFORM_SALES]->collectSalesOrder(
            $salesOrderId,
            $playerIdToPay
        );

        self::ajaxResponse();
    }

    public function confirmDiscard() {
        self::setAjaxMode();

        $cardsToDiscard = self::getArg("cardsToDiscard", AT_numberlist, true);

        $this->game->states[CHECK_HAND_SIZE]->confirmDiscard(
            explode(",", $cardsToDiscard)
        );

        self::ajaxResponse();
    }

    public function confirmGainIdeas() {
        self::setAjaxMode();

        $ideasFromBoard = self::getArg("ideasFromBoard", AT_numberlist, true);
        $ideasFromSupply = self::getArg("ideasFromSupply", AT_numberlist, true);

        $this->game->states[PERFORM_IDEAS]->confirmGainIdeas(
            explode(",", $ideasFromBoard),
            explode(",", $ideasFromSupply)
        );

        self::ajaxResponse();
    }

    public function confirmGainBonusIdeas() {
        self::setAjaxMode();

        $ideas = self::getArg("ideas", AT_numberlist, true);

        $this->game->states[PERFORM_PRINT_BONUS]->confirmGainBonusIdeas(
            explode(",", $ideas)
        );

        self::ajaxResponse();
    }

    public function developComic() {
        self::setAjaxMode();

        $comicId = self::getArg("comicId", AT_posint, true);
        $topOfDeck = self::getArg("topOfDeck", AT_bool, true);

        $this->game->states[PERFORM_DEVELOP]->developComic(
            $comicId,
            $topOfDeck
        );

        self::ajaxResponse();
    }

    public function developFromGenre() {
        self::setAjaxMode();

        $genre = self::getArg("genre", AT_alphanum, true);

        $this->game->states[PERFORM_DEVELOP]->developFromGenre($genre);

        self::ajaxResponse();
    }

    public function doubleTrain() {
        self::setAjaxMode();

        $playerId = self::getArg("playerId", AT_posint, true);
        $comicId = self::getArg("comicId", AT_posint, true);
        $artistId = self::getArg("artistId", AT_posint, true);
        $writerId = self::getArg("writerId", AT_posint, true);

        $this->game->states[INCREASE_CREATIVES]->doubleTrain(
            $playerId,
            $comicId,
            $artistId,
            $writerId
        );

        self::ajaxResponse();
    }

    public function endIncreaseCreatives() {
        self::setAjaxMode();

        $playerId = self::getArg("playerId", AT_posint, true);

        $this->game->states[INCREASE_CREATIVES]->endIncreaseCreatives(
            $playerId
        );

        self::ajaxResponse();
    }

    public function endSales() {
        self::setAjaxMode();

        $this->game->states[PERFORM_SALES]->endSales();

        self::ajaxResponse();
    }

    public function flipSalesOrder() {
        self::setAjaxMode();

        $salesOrderId = self::getArg("salesOrderId", AT_posint, true);
        $playerIdToPay = self::getArg("playerIdToPay", AT_posint, true);

        $this->game->states[PERFORM_SALES]->flipSalesOrder(
            $salesOrderId,
            $playerIdToPay
        );

        self::ajaxResponse();
    }

    public function hireCreative() {
        self::setAjaxMode();

        $cardId = self::getArg("cardId", AT_posint, true);
        $creativeType = self::getArg("creativeType", AT_alphanum, true);

        $this->game->states[PERFORM_HIRE]->hireCreative($cardId, $creativeType);

        self::ajaxResponse();
    }

    public function learn() {
        self::setAjaxMode();

        $playerId = self::getArg("playerId", AT_posint, true);
        $comicId = self::getArg("comicId", AT_posint, true);
        $cardId = self::getArg("cardId", AT_posint, true);

        $this->game->states[INCREASE_CREATIVES]->learn(
            $playerId,
            $comicId,
            $cardId
        );

        self::ajaxResponse();
    }

    public function moveSalesAgent() {
        self::setAjaxMode();

        $space = self::getArg("space", AT_posint, true);

        $this->game->states[PERFORM_SALES]->moveSalesAgent($space);

        self::ajaxResponse();
    }

    public function moveSalesAgentWithTicket() {
        self::setAjaxMode();

        $space = self::getArg("space", AT_posint, true);

        $this->game->states[PERFORM_SALES]->moveSalesAgentWithTicket($space);

        self::ajaxResponse();
    }

    public function placeUpgradeCube() {
        self::setAjaxMode();

        $actionKey = self::getArg("actionKey", AT_posint, true);
        $cubeMoved = self::getArg("cubeMoved", AT_posint, true);

        $this->game->states[PERFORM_PRINT_UPGRADE]->placeUpgradeCube(
            $actionKey,
            $cubeMoved
        );

        self::ajaxResponse();
    }

    public function printComic() {
        self::setAjaxMode();

        $comicId = self::getArg("comicId", AT_posint, true);
        $artistId = self::getArg("artistId", AT_posint, true);
        $writerId = self::getArg("writerId", AT_posint, true);

        $this->game->states[PERFORM_PRINT]->printComic(
            $comicId,
            $artistId,
            $writerId
        );

        self::ajaxResponse();
    }

    public function selectActionSpace() {
        self::setAjaxMode();

        $actionSpace = self::getArg("actionSpace", AT_posint, true);

        $this->game->states[PLAYER_TURN]->selectActionSpace($actionSpace);

        self::ajaxResponse();
    }

    public function selectComicForOrder() {
        self::setAjaxMode();

        $comicId = self::getArg("comicId", AT_posint, true);
        $salesOrderId = self::getArg("salesOrderId", AT_posint, true);

        $this->game->states[PERFORM_SALES_FULFILL_ORDER]->selectComicForOrder(
            $comicId,
            $salesOrderId
        );

        self::ajaxResponse();
    }

    public function selectStartItems() {
        self::setAjaxMode();

        $comicGenre = self::getArg("comic", AT_posint, true);
        $ideaGenres = self::getArg("ideas", AT_numberlist, true);

        $this->game->states[PLAYER_SETUP]->selectStartItems(
            $comicGenre,
            explode(",", $ideaGenres)
        );

        self::ajaxResponse();
    }

    public function selectUpgradeCube() {
        self::setAjaxMode();

        $cubeLocation = self::getArg("actionKey", AT_posint, true);

        $this->game->states[PERFORM_PRINT_GET_UPGRADE_CUBE]->selectUpgradeCube(
            $cubeLocation
        );

        self::ajaxResponse();
    }

    public function skipDoublePrint() {
        self::setAjaxMode();

        $this->game->states[PERFORM_PRINT]->skipDoublePrint();

        self::ajaxResponse();
    }

    public function skipUpgrade() {
        self::setAjaxMode();

        $this->game->states[PERFORM_PRINT_GET_UPGRADE_CUBE]->skipUpgrade();

        self::ajaxResponse();
    }

    public function takeRoyalties() {
        self::setAjaxMode();

        $amount = self::getArg("amount", AT_posint, true);
        $space = self::getArg("space", AT_posint, true);

        $this->game->takeRoyalties($amount, $space);

        self::ajaxResponse();
    }

    public function train() {
        self::setAjaxMode();

        $playerId = self::getArg("playerId", AT_posint, true);
        $comicId = self::getArg("comicId", AT_posint, true);
        $cardId = self::getArg("cardId", AT_posint, true);

        $this->game->states[INCREASE_CREATIVES]->train(
            $playerId,
            $comicId,
            $cardId
        );

        self::ajaxResponse();
    }

    /* SPECIAL ACTION: HYPE (Develop Upgrade) */
    public function hypeComic() {
        self::setAjaxMode();
        $cardId = self::getArg("cardId", AT_posint, true);
        $this->game->states[PERFORM_HYPE]->hypeComic($cardId);
        self::ajaxResponse();
    }

    public function skipHype() {
        self::setAjaxMode();
        $this->game->states[PERFORM_HYPE]->skipHype();
        self::ajaxResponse();
    }

    /* SPECIAL ACTION: MARKETING (Royalties Upgrade) */
    public function payMarketing() {
        self::setAjaxMode();
        // Amount is 2, 5, or 9. comicId is where the fans go.
        $amount = self::getArg("amount", AT_posint, true); 
        $comicId = self::getArg("comicId", AT_posint, true);
        $this->game->states[PERFORM_MARKETING]->payMarketing($amount, $comicId);
        self::ajaxResponse();
    }

    public function skipMarketing() {
        self::setAjaxMode();
        $this->game->states[PERFORM_MARKETING]->skipMarketing();
        self::ajaxResponse();
    }

    /* SPECIAL ACTION: EXTRA EDITOR (Sales Upgrade) */
    public function gainExtraEditor() {
        self::setAjaxMode();
        $this->game->states[PERFORM_EXTRA_EDITOR]->gainExtraEditor();
        self::ajaxResponse();
    }

    /* SPECIAL ACTION: REASSIGN (Hire Upgrade) */
    public function reassignCreatives() {
        self::setAjaxMode();
        // This usually requires swapping a card in hand with one on the mat
        $handCardId = self::getArg("handCardId", AT_posint, true);
        $matCardId = self::getArg("matCardId", AT_posint, true);
        $this->game->states[PERFORM_REASSIGN]->reassignCreatives($handCardId, $matCardId);
        self::ajaxResponse();
    }

    public function skipReassign() {
        self::setAjaxMode();
        $this->game->states[PERFORM_REASSIGN]->skipReassign();
        self::ajaxResponse();
    }

    /* SPECIAL ACTION: CONVERT IDEAS (Ideas Upgrade) */
    public function convertIdeas() {
        self::setAjaxMode();
        // List of Idea Types and which Comic IDs they go to
        $ideasToConvert = self::getArg("ideasToConvert", AT_numberlist, true); 
        $targetComicIds = self::getArg("targetComicIds", AT_numberlist, true);
        
        $this->game->states[PERFORM_CONVERT_IDEAS]->convertIdeas(
            explode(",", $ideasToConvert), 
            explode(",", $targetComicIds)
        );
        self::ajaxResponse();
    }

    public function skipConvertIdeas() {
        self::setAjaxMode();
        $this->game->states[PERFORM_CONVERT_IDEAS]->skipConvertIdeas();
        self::ajaxResponse();
    }
    
    /* SPECIAL ACTION: BETTER COLORS (Print Bonus) */
    public function gainBetterColor() {
        self::setAjaxMode();
        // No arguments needed, it automatically applies to the last printed comic
        $this->game->states[PERFORM_BETTER_COLORS]->gainBetterColor();
        self::ajaxResponse();
    }
}
