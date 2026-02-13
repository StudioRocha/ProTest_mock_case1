<aside class="p-chat__sidebar">
    <h2 class="p-chat__sidebar-title">その他の取引</h2>
    <nav>
        <ul class="p-chat__sidebar-list">
            @forelse($otherTradingOrders as $otherOrder)
            <li>
                <a href="{{ route('chat.index', $otherOrder->item_id) }}" class="p-chat__sidebar-item">
                    {{ $otherOrder->item->item_names }}
                </a>
            </li>
            @empty
            <li class="p-chat__sidebar-empty"></li>
            @endforelse
        </ul>
    </nav>
</aside>
