<?php

function createElement(string $name, int $chance, int $value): stdClass
{
    $element = new stdClass();
    $element->name = $name;
    $element->chance = $chance;
    $element->value = $value;

    return $element;
}

$elements = [
    createElement("*", 19, 5),
    createElement("X", 43, 2),
    createElement("$", 68, 3),
    createElement("@", 94, 2),
    createElement("7", 101, 100),
];

class Dimension
{
    public $rows;
    public $columns;

    public function __construct($rows, $columns)
    {
        $this->rows = $rows;
        $this->columns = $columns;
    }
}

class Board
{
    public $rows;
    public $columns;
    public $grid;

    public function __construct($rows, $columns, $elements)
    {
        $this->rows = $rows;
        $this->columns = $columns;
        $this->generateBoard($elements);
    }

    private function generateBoard($elements)
    {
        $this->grid = [];
        $weightedElements = $this->generateWeightedElements($elements);

        for ($i = 0; $i < $this->rows; $i++) {
            $row = [];
            for ($j = 0; $j < $this->columns; $j++) {
                $randomElement = $weightedElements[array_rand($weightedElements)];
                $row[] = $randomElement;
            }
            $this->grid[] = $row;
        }
    }

    private function generateWeightedElements($elements)
    {
        $weightedElements = [];
        foreach ($elements as $element) {
            for ($i = 0; $i < $element->chance; $i++) {
                $weightedElements[] = $element;
            }
        }
        return $weightedElements;
    }

    public function display()
    {
        foreach ($this->grid as $row) {
            echo implode(" ", array_map(function ($element) {
                    return $element->name;
                }, $row)) . PHP_EOL;
        }
    }

    public function calculateWin($winCondition)
    {
        $win = false;
        $winningElement = null;

        switch ($winCondition) {
            case 'rowcolumn':
                foreach ($this->grid as $row) {
                    if (count(array_unique(array_map(function ($element) {
                            return $element->name;
                        }, $row))) === 1) {
                        $win = true;
                        $winningElement = $row[0];
                        break;
                    }
                }

                if (!$win) {
                    for ($i = 0; $i < $this->columns; $i++) {
                        $column = array_column($this->grid, $i);
                        if (count(array_unique(array_map(function ($element) {
                                return $element->name;
                            }, $column))) === 1) {
                            $win = true;
                            $winningElement = $column[0];
                            break;
                        }
                    }
                }
                break;

            case 'diagonals':
                $diag1 = array_map(function ($i) {
                    return $this->grid[$i][$i];
                }, array_keys($this->grid));

                $diag2 = array_map(function ($i) {
                    $j = count($this->grid) - $i - 1;
                    return $this->grid[$i][$j];
                }, array_keys($this->grid));

                if (count(array_unique(array_map(function ($element) {
                        return $element->name;
                    }, $diag1))) === 1) {
                    $win = true;
                    $winningElement = $diag1[0];
                } elseif (count(array_unique(array_map(function ($element) {
                        return $element->name;
                    }, $diag2))) === 1) {
                    $win = true;
                    $winningElement = $diag2[0];
                }
                break;

            case 'anyrow':
                foreach ($this->grid as $row) {
                    $uniqueSymbols = array_unique(array_map(function ($element) {
                        return $element->name;
                    }, $row));
                    if (count($uniqueSymbols) === 1 && $uniqueSymbols[0] !== ' ') {
                        $win = true;
                        $winningElement = $row[0];
                        break;
                    }
                }
                break;

            default:
                break;
        }

        return ['win' => $win, 'element' => $winningElement];
    }
}

function newDimensions($input)
{
    $dimensions = explode('x', $input);

    if (count($dimensions) != 2) {
        return false;
    }

    $rows = intval(trim($dimensions[0]));
    $columns = intval(trim($dimensions[1]));

    return new Dimension($rows, $columns);
}

$baseBet = 5;

$coins = (int)readline("Enter the amount of coins you'd like to start with: ");
if (empty($coins) || $coins < 0) {
    echo "Input a valid amount." . PHP_EOL;
    exit;
}

$boardSize = readline("Enter the size of the board you'd like (ex. 3x3): ");
$dimensions = newDimensions($boardSize);

if ($dimensions === false) {
    echo "Invalid input format. Please enter dimensions in the format 'NxM'." . PHP_EOL;
    exit;
}

$winCondition = null;

while (true) {
    $input = ucfirst(strtolower(readline("Please input your desired action [Playgame, Bet, Board, Selectwin, Exit]: ")));

    switch ($input) {
        case "Playgame":
            if ($winCondition === null) {
                echo "Please select a win condition before playing the game." . PHP_EOL;
                break;
            }

            $totalCoinsWon = 0;

            do {
                $board = new Board($dimensions->rows, $dimensions->columns, $elements);
                $board->display();

                $winResult = $board->calculateWin($winCondition);
                if ($winResult['win']) {
                    echo "Congratulations! You win!" . PHP_EOL;
                    $wonCoins = $baseBet * $winResult['element']->value;
                    echo "You won $wonCoins coins!" . PHP_EOL;
                    $totalCoinsWon += $wonCoins;
                } else {
                    echo "Sorry, you lose!" . PHP_EOL;
                    $coins -= $baseBet;
                }

                echo "Coins Left: $coins" . PHP_EOL;
                echo PHP_EOL;
                $continue = readline("Do you want to play again? (Y/N): ");
            } while (strtoupper($continue) === 'Y');

            $coins += $totalCoinsWon;
            break;

        case "Bet":
            $baseBet = (int)readline("Please select your bet amount: ");
            break;

        case "Board":
            $newBoardSize = readline("Enter the new size of the board (ex. 3x3): ");
            $newDimensions = newDimensions($newBoardSize);
            if ($newDimensions === false) {
                echo "Invalid input format. Please enter dimensions in the format 'NxM'." . PHP_EOL;
            } else {
                $dimensions = $newDimensions;
            }
            break;

        case "Selectwin":
            $winOptions = ['rowcolumn', 'diagonals', 'anyrow'];
            $winCondition = strtolower(readline("Please select the win condition [RowColumn, Diagonals, AnyRow]: "));
            if (in_array($winCondition, $winOptions)) {
                echo "Win condition selected: $winCondition" . PHP_EOL;
            } else {
                echo "Invalid win condition. Please try again." . PHP_EOL;
            }
            break;

        case "Exit":
            exit("Goodbye!");

        default:
            echo "Invalid input. Please try again." . PHP_EOL;
            break;
    }
}
