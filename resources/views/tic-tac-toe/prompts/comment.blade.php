@if (!empty($userComment))
## 対戦相手からのコメント

{{ $userComment }}

@endif
## ボードの状況履歴

@foreach ($players as $player)
- {{ $player->getSymbol() }}: {{ $player->getName() }} （{{ $player->getType()->value }}）
@endforeach

@foreach ($histories as $index => $history)
### ターン{{ $index + 1 }}、{{ $history['player']->getName() }}の選択 {{ $history['cell'] }}

{{ $history['board'] }}

@endforeach

## 対戦結果

{{ $resultText }}

## あなたのすること

対戦相手へのコメントを100文字以内で返してください。
