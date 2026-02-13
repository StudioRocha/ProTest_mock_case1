(function() {
    'use strict';
    
    // ============================================
    // 初期化: データ属性から設定値を取得
    // ============================================
    const chatContainer = document.querySelector('.p-chat');
    if (!chatContainer) return;
    
    const config = {
        isBuyer: chatContainer.dataset.isBuyer === '1',
        saveDraftUrl: chatContainer.dataset.saveDraftUrl,
        csrfToken: chatContainer.dataset.csrfToken,
        updateUrlTemplate: chatContainer.dataset.updateUrlTemplate,
        destroyUrlTemplate: chatContainer.dataset.destroyUrlTemplate,
        markAsReadUrl: chatContainer.dataset.markAsReadUrl
    };
    
    // ============================================
    // ユーティリティ関数
    // ============================================
    
    /**
     * 入力情報をセッションに保存
     */
    function saveDraftMessage(message) {
        fetch(config.saveDraftUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ message: message })
        });
    }
    
    /**
     * 既読情報を更新（非同期処理）
     */
    function markAsRead() {
        if (!config.markAsReadUrl) return;
        
        fetch(config.markAsReadUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            }
        }).catch(function(error) {
            console.error('既読情報の更新に失敗しました:', error);
        });
    }
    
    /**
     * モーダルを表示
     */
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
        }
    }
    
    /**
     * モーダルを非表示
     */
    function hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    // ============================================
    // 入力情報保持機能
    // ============================================
    const messageInput = document.querySelector('input[name="message"]');
    let saveTimeout = null;
    
    if (messageInput) {
        // 入力中: 500ms後に自動保存
        messageInput.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                saveDraftMessage(messageInput.value);
            }, 500);
        });
        
        // フォーカスアウト時: 即座に保存
        messageInput.addEventListener('blur', function() {
            clearTimeout(saveTimeout);
            saveDraftMessage(messageInput.value);
        });
    }
    
    // ============================================
    // 画像アップロード機能
    // ============================================
    const imageBtn = document.getElementById('imageBtn');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const removeImageBtn = document.getElementById('removeImageBtn');
    
    if (imageBtn && imageInput) {
        // 画像追加ボタンクリックでファイル選択ダイアログを開く
        imageBtn.addEventListener('click', function() {
            imageInput.click();
        });
        
        // ファイル選択時の処理
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // ファイルサイズチェック（5MB以下）
                if (file.size > 5 * 1024 * 1024) {
                    alert('画像ファイルは5MB以下にしてください。');
                    imageInput.value = '';
                    return;
                }
                
                // 画像プレビューを表示
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // 画像削除ボタン
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            imageInput.value = '';
            imagePreview.style.display = 'none';
            previewImg.src = '';
        });
    }
    
    // ============================================
    // 取引完了機能（購入者のみ）
    // ============================================
    if (config.isBuyer) {
        // 購入者の評価モーダル
        const completeBtn = document.getElementById('completeTransactionBtn');
        const cancelCompleteBtn = document.getElementById('cancelCompleteBtn');
        
        if (completeBtn) {
            completeBtn.addEventListener('click', function() {
                showModal('completeModal');
            });
        }
        
        if (cancelCompleteBtn) {
            cancelCompleteBtn.addEventListener('click', function() {
                hideModal('completeModal');
            });
        }
        
        // 星評価のホバー・選択効果
        const starLabels = document.querySelectorAll('.p-chat__star-label');
        const starInputs = document.querySelectorAll('.p-chat__modal-stars input[type="radio"]');
        
        starLabels.forEach(function(label, index) {
            // ホバー時: その星と前の星を黄色にする
            label.addEventListener('mouseenter', function() {
                for (let i = 0; i <= index; i++) {
                    starLabels[i].setAttribute('data-hover', 'true');
                }
            });
            
            label.addEventListener('mouseleave', function() {
                starLabels.forEach(function(l) {
                    l.removeAttribute('data-hover');
                });
            });
            
            // クリック時: 選択状態を更新
            label.addEventListener('click', function() {
                updateStarSelection(index);
            });
        });
        
        starInputs.forEach(function(input, index) {
            input.addEventListener('change', function() {
                updateStarSelection(index);
            });
        });
        
        function updateStarSelection(selectedIndex) {
            starLabels.forEach(function(label, index) {
                if (index <= selectedIndex) {
                    label.setAttribute('data-selected', 'true');
                } else {
                    label.removeAttribute('data-selected');
                }
            });
        }
    }
    
    // ============================================
    // 出品者評価機能（購入者が評価済みの場合）
    // ============================================
    const sellerRateModal = document.getElementById('sellerRateModal');
    if (sellerRateModal) {
        // ページ読み込み時に出品者評価モーダルを自動表示
        showModal('sellerRateModal');
        
        // 出品者評価の星評価のホバー・選択効果
        const sellerStarLabels = sellerRateModal.querySelectorAll('.p-chat__star-label');
        const sellerStarInputs = sellerRateModal.querySelectorAll('.p-chat__modal-stars input[type="radio"]');
        
        sellerStarLabels.forEach(function(label, index) {
            label.addEventListener('mouseenter', function() {
                for (let i = 0; i <= index; i++) {
                    sellerStarLabels[i].setAttribute('data-hover', 'true');
                }
            });
            
            label.addEventListener('mouseleave', function() {
                sellerStarLabels.forEach(function(l) {
                    l.removeAttribute('data-hover');
                });
            });
            
            label.addEventListener('click', function() {
                updateSellerStarSelection(index);
            });
        });
        
        sellerStarInputs.forEach(function(input, index) {
            input.addEventListener('change', function() {
                updateSellerStarSelection(index);
            });
        });
        
        function updateSellerStarSelection(selectedIndex) {
            sellerStarLabels.forEach(function(label, index) {
                if (index <= selectedIndex) {
                    label.setAttribute('data-selected', 'true');
                } else {
                    label.removeAttribute('data-selected');
                }
            });
        }
    }
    
    // ============================================
    // メッセージ編集機能
    // ============================================
    document.querySelectorAll('.p-chat__message-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const messageId = this.dataset.messageId;
            const messageContent = this.closest('.p-chat__message-content');
            const messageBubble = messageContent.querySelector('.p-chat__message-bubble');
            const messageText = messageBubble.textContent.trim();
            
            const editForm = document.getElementById('editMessageForm');
            const editTextarea = document.getElementById('editModal').querySelector('textarea[name="message"]');
            
            editForm.action = config.updateUrlTemplate.replace(':messageId', messageId);
            editTextarea.value = messageText;
            showModal('editModal');
        });
    });
    
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            hideModal('editModal');
        });
    }
    
    // ============================================
    // メッセージ削除機能
    // ============================================
    document.querySelectorAll('.p-chat__message-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('このメッセージを削除しますか？')) {
                return;
            }
            
            const messageId = this.dataset.messageId;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = config.destroyUrlTemplate.replace(':messageId', messageId);
            
            // CSRFトークンを追加
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = config.csrfToken;
            form.appendChild(csrfInput);
            
            // DELETEメソッドを追加
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        });
    });
    
    // ============================================
    // 既読情報更新機能（ブラウザバック対応）
    // ============================================
    // チャット画面を開いた時点で既読情報を更新することで、
    // ブラウザバックでマイページに戻った時も既に既読になっている
    
    // ページ表示時（通常の読み込みとブラウザバックの両方）に既読情報を更新
    window.addEventListener('pageshow', function(event) {
        // ブラウザバックでキャッシュから復元された場合も処理を実行
        // これにより、チャット画面がキャッシュから復元されても既読情報が更新される
        if (event.persisted) {
            markAsRead();
        }
    });
    
    // 通常のページ読み込み時にも既読情報を更新
    // （サーバー側のChatController::index()でも更新しているが、念のため）
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', markAsRead);
    } else {
        markAsRead();
    }
    
    // ============================================
    // 既読位置へのスクロール機能
    // ============================================
    function scrollToLastReadPosition() {
        const lastReadMessage = document.querySelector('[data-last-read="true"]');
        if (lastReadMessage) {
            // レンダリング完了を待ってからスクロール（アニメーションなし）
            setTimeout(function() {
                lastReadMessage.scrollIntoView({
                    behavior: 'auto',
                    block: 'center'
                });
            }, 100);
        }
    }
    
    // ページ読み込み時に既読位置へスクロール
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scrollToLastReadPosition);
    } else {
        scrollToLastReadPosition();
    }
})();
