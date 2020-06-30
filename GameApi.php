<?php
require_once 'Api.php';
require_once 'Database.php';
require_once 'Game.php';

class GameApi extends Api
{
    public string $apiName = 'game';

    /**
     * Method GET
     * Show current game
     *
     * Request example: curl -L -X GET http://localhost/outstanding-move/api/game
     *
     * http://DOMAIN/api/game
     * @return string
     */
    public function indexAction()
    {
        $database = new Database();
        $game = new Game($database);

        if ($game->getStatus() != '') {
            return $this->response($game->preShow(), 200);
        }
        return $this->response('The game has not been launched once or after the last deleting. To start a new game execute a POST request on /game',400);
    }

    /**
     * Method GET
     * Show specific record (by id). We have only one record in a table, so this function does not make sense.
     *
     * Request example: curl -L -X GET http://localhost/outstanding-move/api/game/1
     *
     * http://DOMAIN/api/game/1
     * @return string
     */
    public function viewAction()
    {
        return $this->response('Here is only 1 game. Therefore this command is prohibited', 405);
    }

    /**
     * Method POST
     * Start a new game
     *
     * Request example: curl -L -X POST http://localhost/outstanding-move/api/game
     *
     * http://DOMAIN/game
     * @return string
     */
    public function createAction()
    {
        $database = new Database();
        $game = new Game($database);

        $game->startNewGame($database);
        return $this->response('New game started successfully!', 200);
    }

    /**
     * Method PUT
     * Make a step a chess piece
     *
     * Request example: curl -L -X PUT http://localhost/outstanding-move/api/game -d from=a2 -d to=a3
     *
     * http://DOMAIN/game + request parameters to, from
     * @return string
     */
    public function updateAction()
    {
        $database = new Database();
        $game = new Game($database);

        switch ($game->getStatus()) {
            case 'is_on':   // you can make moves only if the game is on (exists and has not finished yet)
                $from = $this->requestParams['from'] ?? '';
                $to = $this->requestParams['to'] ?? '';

                if (Game::isChessboardPosition($from) && Game::isChessboardPosition($to)) {
                    if ($game->isColorLegalFrom($from)) {
                        if ($game->isColorLegalTo($to)) {
                            if ($game->canAccessPosition($from, $to)) {
                                $game->makeMove($database, $from, $to);
                                if ($game->getStatus() != 'is_on') {
                                    $colorWon = $game->getStatus() == 'white_won' ? 'whites' : 'blacks';
                                    return $this->response('OUTSTANDING MOVE!!!!!!!!!!!!!!!!!!!!!! The chess game is over!!! Congratulations to the ' . $colorWon . '!!!', 200);
                                }
                                return $this->response('Move successfully made', 200);
                            } else {
                                return $this->response("The piece cannot move from " . $from . " to " . $to . " because it cannot make such a movement or there are other chess pieces between positions", 422);
                            }
                        } else {
                            return $this->response("On " . $to . " is your own chess piece. I am sorry, but you cannot make such step", 422);
                        }
                    } else {
                        return $this->response("Illegal move from 'from'. There is not a piece of current player", 422);
                    }
                } else {
                    return $this->response("Incorrect 'from' or 'to' argument. They both should be a check board position in the format like 'e4'", 422);
                }
                break;
            case '':
                return $this->response('To make moves firstly start a new game. There is no game on right now. To start a new game execute a POST request on /game', 400);
            default:    // 'white_won' or 'black_won'
                return $this->response('The game is over. Please, start a new game to make moves. To start a new game execute a POST request on /game', 400);
        }
    }

    /**
     * Method DELETE
     * Delete current game if it exists
     *
     * @return string
     */
    public function deleteAction()
    {
        $database = new Database();
        $game = new Game($database);

        if ($game->getStatus() == '') {
            return $this->response('Game does not exist already!', 400);
        }
        $game->deleteGame($database);
        return $this->response('Game successfully deleted!', 200);
    }

}