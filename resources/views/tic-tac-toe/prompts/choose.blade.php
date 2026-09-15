## 現在のボードの状況

{!! $board !!}

@foreach ($players as $player)
- {{ $player->getSymbol() }}: {{ $player->getName() }} （{{ $player->getType()->value }}）
@endforeach

## 選択可能なセル

@foreach ($availableCells as $cell)
- {{ $cell[0] + 1 }}行 {{ $cell[1] + 1 }}列
@endforeach

## 次の一手を選んでください。

次の一手の行番号と列番号を答えてください。
