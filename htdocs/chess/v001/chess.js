window.onload = function () {
    initGame();
}

function initGame() {
    createBoard();
}

function createBoard() {
    const BoardSize = 8;
    board = document.querySelector('.board');
    board.innerHTML = '';
    for (let i = 0; i < BoardSize**2; i += 1) {
        const cell = document.createElement('div');
        let is_white = (parseInt(i / BoardSize) + (i % BoardSize)) % 2 == 0;
        cell.classList.add('cell', (is_white ? 'white' : 'black'));
        board.appendChild(cell);
    }
}