<?php
// Если запрос пришел через AJAX для генерации нового поля
if (isset($_GET['action']) &&$_GET['action'] === 'generate') {
    header('Content-Type: application/json');
    
    $rows = isset($_GET['rows']) ? max(5, min(30, (int)$_GET['rows'])) : 10;
    $cols = isset($_GET['cols']) ? max(5, min(30, (int)$_GET['cols'])) : 10;
    $minesCount = isset($_GET['mines']) ? max(1, min($rows * $cols - 1, (int)$_GET['mines'])) : 15;

    // 1. Создаем пустое поле
    $board = array_fill(0, $rows, array_fill(0,$cols, 0));

    // 2. Расставляем мины (-1)
    $placedMines = 0;
    while ($placedMines <$minesCount) {
        $r = rand(0,$rows - 1);
        $c = rand(0,$cols - 1);
        if ($board[$r][$c] !== -1) {$board[$r][$c] = -1;
            $placedMines++;
        }
    }

    // 3. Высчитываем цифры вокруг мин
    for ($r = 0; $r < $rows; $r++) {
        for ($c = 0; $c < $cols; $c++) {
            if ($board[$r][$c] === -1) continue;

            $count = 0;
            for ($dr = -1; $dr <= 1; $dr++) {
                for ($dc = -1; $dc <= 1; $dc++) {$nr = $r +$dr;
                    $nc = $c +$dc;
                    if ($nr >= 0 &&$nr < $rows &&$nc >= 0 && $nc <$cols) {
                        if ($board[$nr][$nc] === -1) {$count++;
                        }
                    }
                }
            }
            $board[$r][$c] =$count;
        }
    }

    echo json_encode([
        'rows' => $rows,
        'cols' => $cols,
        'mines' => $minesCount,
        'board' => $board
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Сапер (Single File)</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #1e1e2e;
            color: #cdd6f4;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        h1 { margin-bottom: 10px; color: #f9e2af; }
        .controls {
            background: #313244;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .controls label { font-size: 14px; }
        .controls input {
            width: 50px;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #45475a;
            background: #181825;
            color: #fff;
            text-align: center;
        }
        button {
            background: #a6e3a1;
            color: #11111b;
            border: none;
            padding: 8px 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.2s;
        }
        button:hover { background: #94e2d5; }
        .status {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            height: 25px;
        }
        #game-board {
            display: grid;
            gap: 4px;
            background: #313244;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
            user-select: none;
        }
        .cell {
            width: 35px;
            height: 35px;
            background: #45475a;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.1s;
        }
        .cell:hover { background: #585b70; }
        .cell.revealed { background: #181825; cursor: default; }
        .cell.flagged { color: #f38ba8; }
        .cell.mine { background: #f38ba8 !important; }

        .val-1 { color: #89b4fa; }
        .val-2 { color: #a6e3a1; }
        .val-3 { color: #f38ba8; }
        .val-4 { color: #cba6f7; }
        .val-5 { color: #fab387; }
        .val-6 { color: #94e2d5; }
        .val-7 { color: #f9e2af; }
        .val-8 { color: #b4befe; }
    </style>
</head>
<body>

    <h1>💣 Сапер (PHP в одном файле)</h1>

    <div class="controls">
        <label>Строк: <input type="number" id="rows" value="10" min="5" max="30"></label>
        <label>Столбцов: <input type="number" id="cols" value="10" min="5" max="30"></label>
        <label>Мин: <input type="number" id="mines" value="15" min="1"></label>
        <button onclick="initGame()">Новая игра</button>
    </div>

    <div class="status" id="status">Флажки: 0 / 15</div>
    <div id="game-board"></div>

    <script>
        let boardData = [];
        let rows = 10, cols = 10, totalMines = 15;
        let revealedCells = 0;
        let gameOver = false;
        let flagsCount = 0;

        function initGame() {
            rows = parseInt(document.getElementById('rows').value);
            cols = parseInt(document.getElementById('cols').value);
            totalMines = parseInt(document.getElementById('mines').value);

            if (totalMines >= rows * cols) {
                alert('Мин слишком много для такого поля!');
                return;
            }

            // Запрос к этому же файлу с параметром action=generate
            fetch(`?action=generate&rows=${rows}&cols=${cols}&mines=${totalMines}`)
                .then(res => res.json())
                .then(data => {
                    boardData = data.board;
                    revealedCells = 0;
                    gameOver = false;
                    flagsCount = 0;
                    document.getElementById('status').style.color = '#cdd6f4';
                    updateFlagsDisplay();
                    renderBoard();
                });
        }

        function updateFlagsDisplay() {
            document.getElementById('status').innerHTML = `Флажки: ${flagsCount} / ${totalMines}`;
        }

        function renderBoard() {
            const boardEl = document.getElementById('game-board');
            boardEl.innerHTML = '';
            boardEl.style.gridTemplateColumns = `repeat(${cols}, 35px)`;

            for (let r = 0; r < rows; r++) {
                for (let c = 0; c < cols; c++) {
                    const cell = document.createElement('div');
                    cell.classList.add('cell');
                    cell.dataset.row = r;
                    cell.dataset.col = c;

                    cell.addEventListener('click', () => revealCell(r, c));
                    cell.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        toggleFlag(cell);
                    });

                    boardEl.appendChild(cell);
                }
            }
        }

        function toggleFlag(cell) {
            if (gameOver || cell.classList.contains('revealed')) return;

            if (cell.classList.contains('flagged')) {
                cell.classList.remove('flagged');
                cell.textContent = '';
                flagsCount--;
            } else {
                cell.classList.add('flagged');
                cell.textContent = '🚩';
                flagsCount++;
            }
            updateFlagsDisplay();
        }

        function revealCell(r, c) {
            if (gameOver) return;

            const cell = document.querySelector(`[data-row="${r}"][data-col="${c}"]`);
            if (!cell || cell.classList.contains('revealed') || cell.classList.contains('flagged')) return;

            cell.classList.add('revealed');
            const val = boardData[r][c];

            if (val === -1) {
                cell.classList.add('mine');
                cell.textContent = '💣';
                endGame(false);
                return;
            }

            revealedCells++;

            if (val > 0) {
                cell.textContent = val;
                cell.classList.add(`val-${val}`);
            } else {
                for (let dr = -1; dr <= 1; dr++) {
                    for (let dc = -1; dc <= 1; dc++) {
                        const nr = r + dr;
                        const nc = c + dc;
                        if (nr >= 0 && nr < rows && nc >= 0 && nc < cols) {
                            revealCell(nr, nc);
                        }
                    }
                }
            }

            if (revealedCells === (rows * cols - totalMines)) {
                endGame(true);
            }
        }

        function endGame(isWin) {
            gameOver = true;
            const statusEl = document.getElementById('status');

            if (isWin) {
                statusEl.textContent = '🎉 ВЫ ПОБЕДИЛИ!';
                statusEl.style.color = '#a6e3a1';
            } else {
                statusEl.textContent = '💥 БУМ! ИГРА ОКОНЧЕНА!';
                statusEl.style.color = '#f38ba8';

                for (let r = 0; r < rows; r++) {
                    for (let c = 0; c < cols; c++) {
                        if (boardData[r][c] === -1) {
                            const cell = document.querySelector(`[data-row="${r}"][data-col="${c}"]`);
                            cell.classList.add('revealed', 'mine');
                            cell.textContent = '💣';
                        }
                    }
                }
            }
        }

        initGame();
    </script>
</body>
</html>
