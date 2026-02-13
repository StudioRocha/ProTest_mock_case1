<!-- 取引完了モーダル（購入者のみ） -->
@if($isBuyer)
<div class="p-chat__modal" id="completeModal" style="display: none;">
    <div class="p-chat__modal-content p-chat__modal-content--complete">
        <h2 class="p-chat__modal-title">取引が完了しました。</h2>
        <form action="{{ route('chat.complete', $item) }}" method="POST" id="completeForm">
            @csrf
            <div class="p-chat__modal-rating">
                <p class="p-chat__modal-rating-question">今回の取引相手はどうでしたか？</p>
                <div class="p-chat__modal-stars">
                    <input type="radio" name="rating" id="rating1" value="1" required>
                    <label for="rating1" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                    <input type="radio" name="rating" id="rating2" value="2" required>
                    <label for="rating2" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                    <input type="radio" name="rating" id="rating3" value="3" required>
                    <label for="rating3" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                    <input type="radio" name="rating" id="rating4" value="4" required>
                    <label for="rating4" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                    <input type="radio" name="rating" id="rating5" value="5" required>
                    <label for="rating5" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                </div>
                @if($errors->any())
                <div class="p-chat__modal-errors">
                    @foreach($errors->all() as $error)
                    <div class="p-chat__modal-error">{{ $error }}</div>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="p-chat__modal-actions">
                <button type="submit" class="p-chat__modal-submit">送信する</button>
            </div>
        </form>
    </div>
</div>
@endif
