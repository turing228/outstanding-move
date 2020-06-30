<?php

/**
 * Class Piece.
 *
 * As we have different types of chess pieces, but all of them have
 * the same logic (they have color, they can move, etc.), it is very
 * handful to inherit common abstract class to just call these functions
 * outside without thinking what is exact chess type
 */
abstract class Piece
{
    /**
     * @var string chess piece color. Either 'w' (white) or 'B' (black)
     */
    protected string $color;
    /**
     * @var bool should it be empty between the start and end positions to make a move?
     */
    protected bool $sliding;
    /**
     * @var array minimum description of the types of moves for this type of chess pieces
     */
    protected array $moves;

    /**
     * Piece constructor.
     *
     * @param string $letter the first letter of the name of the type of chess piece. Lower case means white color.
     * Upper case means black color. T = t = Tsar (King)
     */
    public function __construct(string $letter)
    {
        $this->color = getPieceType($letter);
    }

    /**
     * Checks if this piece can jump from $from to $to. Does not check correctness of positions and does not check
     * $to position occupancy
     *
     * @param array $moves minimum description of the types of moves for this type of chess pieces
     * @param int $from number of chessboard cell. a1=0, b1=1, c1=2, ..., a2=8, b2=9,..., h8=63
     * @param int $to number of chessboard cell. a1=0, b1=1, c1=2, ..., a2=8, b2=9,..., h8=63
     * @return bool
     */
    protected function checkPositionsNotSliding($moves, int $from, int $to)
    {
        $x = $from % 8;
        $y = intdiv($from, 8);

        foreach ($moves as $value) {
            $xi = $x + $value['dx'];
            $yi = $y + $value['dy'];
            if ($xi >= 0 and $xi < 8 and
                $yi >= 0 and $yi < 8) {
                if ($yi * 8 + $xi == $to) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks if this piece can "slide" from $from to $to without touching other pieces on the board. Does not check
     * correctness of positions and does not check $to position occupancy
     *
     * @param array $moves minimum description of the types of moves for this type of chess pieces
     * @param int $from number of chessboard cell. a1=0, b1=1, c1=2, ..., a2=8, b2=9,..., h8=63
     * @param int $to number of chessboard cell. a1=0, b1=1, c1=2, ..., a2=8, b2=9,..., h8=63
     * @param string $board string representation of current game board
     * @return bool
     */
    protected function checkPositionsSliding(array $moves, int $from, int $to, string $board)
    {
        $x = $from % 8;
        $y = intdiv($from, 8);

        foreach ($moves as $value) {
            for ($i = 1; $i < 8; $i++) {
                $xi = $x + $i * $value['dx'];
                $yi = $y + $i * $value['dy'];
                if ($xi >= 0 and $xi < 8 and
                    $yi >= 0 and $yi < 8) {
                    if ($yi * 8 + $xi == $to) {
                        return true;
                    }
                    if ($board[$yi * 8 + $xi] != '*') {
                        break;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Checks if this piece can move from $from to $to. Does not check correctness of positions and does not check
     * $to position occupancy
     *
     * @param int $from number of chessboard cell. a1=0, b1=1, c1=2, ..., a2=8, b2=9,..., h8=63
     * @param int $to number of chessboard cell. a1=0, b1=1, c1=2, ..., a2=8, b2=9,..., h8=63
     * @param string $board string representation of current game board
     * @return bool
     */
    public function canAccessPosition(int $from, int $to, string $board)
    {
        return $this->sliding ?
            $this->checkPositionsSliding($this->moves, $from, $to, $board) :
            $this->checkPositionsNotSliding($this->moves, $from, $to);
    }
}

/**
 * @param string $letter the first letter of the name of the type of chess piece. Lower case means white color.
 * Upper case means black color. T = t = Tsar (King)
 * @return string
 */
function getPieceType(string $letter)
{
    if (preg_match('/^[a-z]$/', $letter)) {
        return 'w';
    }
    if (preg_match('/^[A-Z]$/', $letter)) {
        return 'B';
    }
    return '*';
}

/**
 * @param string $letter the first letter of the name of the type of chess piece. Lower case means white color.
 * Upper case means black color. T = t = Tsar (King)
 * @return string
 */
function recognisePiece(string $letter)
{
    switch (strtolower($letter)) {
        case 't':
            return new Tsar($letter);
        case 'k':
            return new Knight($letter);
        case 'p':
            return new Pawn($letter);
        case 'b':
            return new Bishop($letter);
        case 'r':
            return new Rook($letter);
        case 'q':
            return new Queen($letter);
    }

    return null;
}

/**
 * Class Tsar. In classic chess it is king.
 */
class Tsar extends Piece
{
    protected bool $sliding = false;
    protected array $moves = [
        ['dx' => -1, "dy" => -1],
        ['dx' => -1, "dy" => 0],
        ['dx' => -1, "dy" => 1],
        ['dx' => 0, "dy" => -1],
        ['dx' => 0, "dy" => 1],
        ['dx' => 1, "dy" => -1],
        ['dx' => 1, "dy" => 0],
        ['dx' => 1, "dy" => 1],
    ];
}

class Knight extends Piece
{
    protected bool $sliding = false;
    protected array $moves = [
        ['dx' => -2, "dy" => 1],
        ['dx' => -2, "dy" => -1],
        ['dx' => -1, "dy" => -2],
        ['dx' => 1, "dy" => -2],
        ['dx' => 2, "dy" => -1],
        ['dx' => 2, "dy" => 1],
        ['dx' => 1, "dy" => 2],
        ['dx' => 1, "dy" => -2],
    ];
}

class Pawn extends Piece
{
    protected bool $sliding = false;
    private array $movesWhite = [
        ['dx' => -1, "dy" => 1],
        ['dx' => 1, "dy" => 1],
    ];

    private array $movesBlack = [
        ['dx' => -1, "dy" => -1],
        ['dx' => 1, "dy" => -1],
    ];

    public function canAccessPosition(int $from, int $to, string $board)
    {
        $y = intdiv($from, 8);

        if ($this->color == 'w') {
            if ($board[$to] == 'B') {
                return $this->checkPositionsNotSliding($this->movesWhite, $from, $to);
            } else {
                if ($y == 1) {
                    return ($to - $from == 16 and $board[$from + 8] == '*') or $to - $from == 8;
                } else {
                    return $to - $from == 8;
                }
            }
        } else {
            if ($board[$to] == 'w') {
                return $this->checkPositionsNotSliding($this->movesBlack, $from, $to);
            } else {
                if ($y == 6) {
                    return ($to - $from == -16 and $board[$from - 8] == '*') or $to - $from == -8;
                } else {
                    return $to - $from == -8;
                }
            }
        }
    }
}

class Rook extends Piece
{
    protected bool $sliding = true;
    protected array $moves = [
        ['dx' => -1, "dy" => 0],
        ['dx' => 0, "dy" => -1],
        ['dx' => 1, "dy" => 0],
        ['dx' => 0, "dy" => 1],
    ];
}

class Bishop extends Piece
{
    protected bool $sliding = true;
    protected array $moves = [
        ['dx' => -1, "dy" => 1],
        ['dx' => -1, "dy" => -1],
        ['dx' => 1, "dy" => -1],
        ['dx' => 1, "dy" => 1],
    ];
}

class Queen extends Piece
{
    protected bool $sliding = true;
    protected array $moves = [
        ['dx' => -1, "dy" => -1],
        ['dx' => -1, "dy" => 0],
        ['dx' => -1, "dy" => 1],
        ['dx' => 0, "dy" => -1],
        ['dx' => 0, "dy" => 1],
        ['dx' => 1, "dy" => -1],
        ['dx' => 1, "dy" => 0],
        ['dx' => 1, "dy" => 1],
    ];
}