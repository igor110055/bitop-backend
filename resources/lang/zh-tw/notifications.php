<?php

return [
    'sms' => [
        'verification' => '['.config('app.name').'] 驗證碼 :code.',
        'deal_notification' => '['.config('app.name').'] 訂單成立通知 #:order_id，請登入查看訂單資訊',
        'claim_notification' => '['.config('app.name').'] 買家已付款通知 #:order_id，請登入查看訂單資訊',
        'reset_mobile_verification' => '['.config('app.name').'] 驗證碼 :code.',
    ],
    'email' => [
        'email_verification' => [
            'subject' => '['.config('app.name').'] Email 驗證',
            'content' => '你的驗證碼是',
        ],
        'password_verification' => [
            'subject' => '['.config('app.name').'] 密碼重置驗證',
            'content' => '你的密碼重置驗證碼是',
        ],
        'security_code_verification' => [
            'subject' => '['.config('app.name').'] 安全碼重置驗證',
            'content' => '你的安全碼重置驗證碼是',
        ],
        'deactivate_tfa_verification' => [
            'subject' => '['.config('app.name').'] 停用二步驟認證驗證',
            'content' => '你的停用二步驟認證驗證碼是',
        ],
        'reset_email_verification' => [
            'subject' => '['.config('app.name').'] Email 重置驗證',
            'content' => '你的重置驗證碼是',
        ],
        'transfer_verification' => [
            'subject' => '['.config('app.name').'] 劃轉驗證 - :time (UTC)',
            'greeting' => '劃轉請求',
            'content1' => '你的帳號提出了劃轉請求，資訊如下:',
            'content2' => '在你確認劃轉請求之前，請仔細檢查目的帳號，若你確認此提現為自己的操作，請點擊下方按鈕：',
            'action' => '確認劃轉',
            'content3' => '基於安全原因，此連結將於 30 分鐘後失效。',
        ],
        'transfer_notification' => [
            'subject' => '['.config('app.name').'] 劃轉通知 - :time (UTC)',
            'greeting' => '劃轉已到帳!',
            'content1' => '您已於 :time (UTC) 收到來自 :source 的劃轉，數量 :amount :coin',
            'message' => ':source 有一則留言給您: :message',
            'content2' => '請前往交易明細查看更多相關細節，如果對此劃轉有疑慮或是任何問題，請聯絡我們',
        ],
        'deposit_notification' => [
            'subject' => '['.config('app.name').'] 充值成功通知 - :time (UTC)',
            'greeting' => '充值成功!',
            'content1' => '您已於:time (UTC)成功充值:amount :coin',
            'content2' => '請前往交易明細查看更多相關細節，如果對此充值有疑慮或是任何問題，請聯絡我們',
        ],
        'order_confirmation' => [
            'subject' => '['.config('app.name').'] 交易驗證',
            'content' => '你的交易驗證碼是',
        ],
        'withdrawal_verification' => [
            'subject' => '['.config('app.name').'] 提現請求 - :time (UTC)',
            'greeting' => '提現請求',
            'content1' => '你的帳號提出了提現請求，資訊如下:',
            'content2' => '在你確認提現請求之前，請仔細檢查目的地址與 Tag，若你提現至一個錯誤的地址，資產將無法取回。若你理解此風險並確認此提現為你自己所操作，請點擊下方按鈕：',
            'action' => '確認提現',
            'content3' => '基於安全原因，此連結將於 30 分鐘後失效。',
        ],
        'withdrawal_bad_request_notification' => [
            'subject' => '['.config('app.name').'] 提現失敗通知 - :time (UTC)',
            'greeting' => '您的提現已被系統取消',
            'content1' => '您於 :confirmed_time (UTC) 送出的提現：',
            'content2' => '因為資料有誤，已於 :canceled_time (UTC) 被系統取消，可能是地址或是 tag 格式錯誤，請檢查後重新操作。',
            'content3' => '如果對此有疑慮或是任何問題，請聯絡我們',
        ],
        'deal_notification' => [
            'subject' => '['.config('app.name').'] 訂單成立通知 #:order_id',
            'greeting' => '訂單成立通知 #:order_id',
            'content' => [
                'dst_user' => [
                    'buy' => '你向 :username 購買了 :amount :coin。',
                    'sell' => ':username 向你販售了 :amount :coin。',
                ],
                'src_user' => [
                    'buy' => ':username 向你購買了 :amount :coin。',
                    'sell' => '你向 :username 販售了 :amount :coin。',
                ]
            ],
            'action' => '查看訂單',
            'dst_user_reminder' => '請於 :time UTC 之前完成付款，並點選訂單中「確認已付款」按鈕。'
        ],
        'claim_notification' => [
            'subject' => '['.config('app.name').'] 買家已付款通知 #:order_id',
            'greeting' => '買家已付款通知 #:order_id',
            'content' => '訂單號 #:order_id 的買家 :username 表示已付款，請確認收到款項後，至訂單頁面點選「已確認」按鈕。',
            'action' => '查看訂單',
        ],
        'order_revoked_notification' => [
            'subject' => '['.config('app.name').'] 買家撤銷已付款通知 #:order_id',
            'greeting' => '買家撤銷已付款通知 #:order_id',
            'content' => '訂單號 #:order_id 的買家 :username 已撤銷宣稱對此訂單的付款。',
            'action' => '查看訂單',
        ],
        'ad_unavailable_notification' => [
            'subject' => '['.config('app.name').'] 廣告下架通知 #:advertisement_id',
            'greeting' => '廣告下架通知 #:advertisement_id',
            'admin' => [
                'content' => '管理者已經將你的廣告 #:advertisement_id 下架，如有任何問題，請透過客服管道聯繫我們。',
            ],
            'system' => [
                'content' => '由於你的廣告中剩餘加密貨幣的價值已小於最小單筆限額，系統已自動將你的廣告 #:advertisement_id 下架，若要繼續交易，請編輯廣告內容後重新上架。',
            ],
        ],
        'order_completed_dst_notification' => [
            'subject' => '['.config('app.name').'] 訂單完成通知 #:order_id',
            'greeting' => '訂單完成通知 #:order_id',
            'content' => '訂單號 #:order_id 的付款已確認，虛擬貨幣已放行至您的帳戶中。',
            'action' => '查看訂單',
        ],
        'order_completed_src_notification' => [
            'subject' => '['.config('app.name').'] 訂單完成通知 #:order_id',
            'greeting' => '訂單完成通知 #:order_id',
            'content' => '訂單號 #:order_id 的付款已確認，虛擬貨幣已將放行至買家的帳戶中。',
            'action' => '查看訂單',
        ],
        'order_payment_check'=> [
            'subject' => '['.config('app.name').'] 訂單付款待確認通知 #:order_id',
            'greeting' => '訂單付款待確認通知 #:order_id',
            'content' => '訂單號 #:order_id 已收到第三方收到付款的通知，但因為付款範圍在自動放行範圍外，請確認收到付款後，至後台手動放行。',
        ],
        'order_canceled_notification' => [
            'subject' => '['.config('app.name').'] 訂單取消通知 #:order_id',
            'greeting' => '訂單取消通知 #:order_id',
            'user' => [
                'seller' => ['content' => '買家已經取消訂單，如有任何問題，請透過客服管道聯繫我們。'],
                'buyer' => ['content' => '你的訂單已經被取消，如有任何問題，請透過客服管道聯繫我們。'],
            ],
            'system' => [
                'seller' => ['content' => '因為訂單已超過付款時限，但買家並未通知已付款，系統已將訂單取消，如有任何問題，請透過客服管道聯繫我們。'],
                'buyer' => ['content' => '因為訂單已超過付款時限，且未收到你的付款通知，系統已將訂單取消，如有任何問題，請透過客服管道聯繫我們。'],
            ],
            'admin' => ['content' => '管理者已經取消你的訂單，如有任何問題，請透過客服管道聯繫我們。'],
        ],
        'group_invitation_notification' => [
            'subject' => '['.config('app.name').'] 群組邀請通知 [:group_name]',
            'greeting' => '您好，新用戶',
            'content' => '[:group_name] 群組擁有者 :username 邀請您加入 [:group_name] 群組，請點擊以下連結進行註冊。',
            'action' => '註冊連結',
            'content2' => '或是於註冊時填寫邀請碼: :invitation_code。',
            'content3' => '以上連結與邀請碼有效期限為 UTC :expired_at，逾期將失效。',
        ],
        'auth_result_notificaiton' => [
            'guest' => '親愛的用戶',
            'status' => [
                'passed' => '通過',
                'rejected' => '未通過',
            ],
            'subject' => '['.config('app.name').'] 身份驗證:status通知',
            'greeting' => ':name您好',
            'result' => '您的身份驗證結果為「:status」，',
            'explain' => '我們基於以下原因，無法通過您的身份驗證：',
            'follow_up' => [
                'passed' => '從現在開始，您可以使用本網站的各種功能。',
                'rejected' => '請您備妥有效資料後，再次送出身份驗證申請。',
            ],
            'action' => '前往 '.config('app.name'),
        ],
        'bank_account_verification_notificaiton' => [
            'guest' => '親愛的用戶',
            'status' => [
                'approve' => '通過',
                'rejecte' => '未通過',
            ],
            'subject' => '['.config('app.name').'] 銀行帳號審核結果通知',
            'greeting' => ':name你好',
            'result' => [
                'approve' => '我們已通過你於 :bank 的銀行帳號審核，',
                'reject' => '我們審核你了提供的 :bank 的銀行帳號資料，但我們無法讓你於 '. config('app.name').' 使用該銀行帳號。'
            ],
            'explain' => '該銀行帳號未通過審核的原因如下：',
            'follow_up' => [
                'approve' => '你現在已可使用這個銀行帳號進行交易。',
                'reject' => '請重新使用正確的資料新增銀行帳號。',
            ],
            'action' => '前往 '.config('app.name'),
        ],
        'login_fail_user_lock' => [
            'subject' => '['.config('app.name').'] 登入失敗鎖定通知 - :time (UTC)',
            'greeting' => '親愛的用戶 :name',
            'content' => '我們發現你的帳號連續登入失敗達 3 次，我們已阻擋你的帳號從 IP 地址 :IP 登入，鎖定時間 1 小時。',
            'content2' => '若您認為您的帳號有安全性風險，請儘速更換密碼。',
            'content3' => '如有任何問題，請透過客服管道聯繫我們。',
        ],
        'security_code_fail_user_lock' => [
            'subject' => '['.config('app.name').'] 帳號鎖定通知 - :time (UTC)',
            'greeting' => '親愛的用戶 :name',
            'content' => '我們發現你的帳號連續輸入錯誤的安全碼達 3 次，我們已暫時凍結你的帳號 24 小時。',
            'content2' => '若您認為您的帳號有安全性風險，請儘速聯繫我們。',
        ],
    ],
    'push' => [
        'deal_notification' => [
            'subject' => '['.config('app.name').'] 訂單成立通知 #:order_id',
            'content' => '你有一筆:amount :coin的訂單，請登入查看詳情。',
        ],
    ],
];
