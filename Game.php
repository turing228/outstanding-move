<?php
require_once 'Database.php';
require_once 'Piece.php';

class Game
{
    /**
     * @var mixed|string which color turn to move chess pieces? 'w' (white) or 'B' (black)
     */
    private string $color_now;
    /**
     * @var mixed|string string representation of current game board
     */
    private string $board;
    /**
     * @var mixed|string or 'is_on' (in process) or 'white_won' (finished, white won) or 'black_won' (finished, black
     * won) or '' if the game is not running
     */
    private string $status = '';

    /**
     * @param $database Database link to the table at MySQL server
     */
    public function __construct(Database $database)
    {
        if (count($database->query('SHOW TABLES LIKE "game"')->fetchArray()) > 0) {
            $game = $this->getData($database);
            $this->color_now = $game['color_now'];
            $this->board = $game['board'];
            $this->status = $game['status'];
        }
    }

    /**
     * @return mixed|string or 'is_on' (in process) or 'white_won' (finished, white won) or 'black_won' (finished, black
     * won) or '' if the game is not running
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function preShow()
    {
        return array('color_now' => $this->color_now, 'board' => $this->board, 'status' => $this->status);
    }

    private function getData($database)
    {
        return $database->query('SELECT * FROM `game`')->fetchArray();
    }

    public function startNewGame(Database $database)
    {
        $database->query('DROP TABLE IF EXISTS `game`');
        $database->query('
CREATE TABLE `game` (
  `color_now` enum(\'w\',\'B\') NOT NULL DEFAULT \'w\',
  `board` varchar(64) NOT NULL DEFAULT \'rkbqtbkrpppppppp********************************PPPPPPPPRKBQTBKR\',
  `status` enum(\'is_on\',\'white_won\',\'black_won\') NOT NULL DEFAULT \'is_on\',
  `time_created` timestamp NOT NULL DEFAULT current_timestamp()
)');
        $database->query('INSERT INTO `game` () VALUES ()');
    }

    public function deleteGame(Database $database)
    {
        $database->query('DROP TABLE IF EXISTS `game`');
    }

    private function letterPositionToNumber(string $position)
    {
        return (ord($position[0]) - ord('a')) + (ord($position[1]) - ord('1')) * 8;
    }

    private function getPieceLetter(string $position)
    {
        return $this->board[$this->letterPositionToNumber($position)];
    }

    public function isColorLegalFrom(string $from)
    {
        return getPieceType($this->getPieceLetter($from)) == $this->color_now;
    }

    public function isColorLegalTo(string $to)
    {
        return getPieceType($this->getPieceLetter($to)) != $this->color_now;
    }

    public static function isChessboardPosition(string $position)
    {
        return preg_match('/^[a-h][1-8]$/', $position);
    }

    public function canAccessPosition(string $from, string $to)
    {
        $piece = $this->getPieceLetter($from);
        $recognisedPiece = recognisePiece($piece);

        return $recognisedPiece->canAccessPosition($this->letterPositionToNumber($from), $this->letterPositionToNumber($to), $this->board);
    }

    public function makeMove(Database $database, string $from, string $to)
    {
        if (recognisePiece($this->getPieceLetter($to)) instanceof Tsar) {
            $this->status = $this->color_now == 'w' ? 'white_won' : 'black_won';
        }
        $this->board[$this->letterPositionToNumber($to)] = $this->board[$this->letterPositionToNumber($from)];
        $this->board[$this->letterPositionToNumber($from)] = '*';
        $this->color_now = $this->color_now == 'w' ? 'B' : 'w';
        $database->query('UPDATE `game` SET color_now=?, board=?, status=?', $this->color_now, $this->board, $this->status);
    }
}