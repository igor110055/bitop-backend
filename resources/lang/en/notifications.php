<?php

return [
    # Zero width space \u{200B} is added because all english content will be treated as ASCII sms and cause brackets uncorrectly displayed.
    'sms' => [
        'verification' => '['.config('app.name')."]\u{200B} Your verification code is :code.",
        'deal_notification' => '['.config('app.name')."]\u{200B} New order notification #:order_id. Please sign in to check your order.",
        'claim_notification' => '['.config('app.name')."]\u{200B} Buyer paid notification of order #:order_id. Please sign in to check your order.",
        'reset_mobile_verification' => '['.config('app.name')."]\u{200B} Your verification code is :code.",
    ],
    'email' => [
        'email_verification' => [
            'subject' => '['.config('app.name').'] Email Verification Code',
            'content' => 'Your verification code is',
        ],
        'password_verification' => [
            'subject' => '['.config('app.name').'] Password Recovery Code',
            'content' => 'Your password recovery code is',
        ],
        'security_code_verification' => [
            'subject' => '['.config('app.name').'] Security-code Recovery Code',
            'content' => 'Your security-code recovery code is',
        ],
        'deactivate_tfa_verification' => [
            'subject' => '['.config('app.name').'] Deactivate Two-Factor Authentication Code',
            'content' => 'Your deactivate 2FA code is',
        ],
        'reset_email_verification' => [
            'subject' => '['.config('app.name').'] Reset Email Verification Code',
            'content' => 'Your reset verification code is',
        ],
        'transfer_verification' => [
            'subject' => '['.config('app.name').'] Transfer Request - :time (UTC+8)',
            'greeting' => 'Transfer Requested',
            'content1' => 'Your account just issued a transfer request. Transfer information:',
            'content2' => "Before you confirm the transfer, please verify the target account carefully. If you could confirm that this operation is your own action, please click the button below:",
            'action' => 'Confirm Transfer',
            'content3' => 'For the safety of your assets, the button link will expire after 30 minutes.',
        ],
        'transfer_notification' => [
            'subject' => '['.config('app.name').'] Transfer Notification - :time (UTC+8)',
            'greeting' => 'Transfer Received!',
            'content1' => 'You received a transfer of :amount :coin from user :source at :time (UTC+8)',
            'message' => ':source has left a message: :message',
            'content2' => 'Please kindly check your transaction details for more information. If you have any other concerns with this transfer please contact us.',
        ],
        'deposit_notification' => [
            'subject' => '['.config('app.name').'] Deposit Received Notification - :time (UTC+8)',
            'greeting' => 'Deposit Success!',
            'content1' => 'You have successfully deposit :amount :coin in your account at :time (UTC+8)',
            'content2' => 'Please kindly check your transaction details for more information. If you have any other concerns with this deposit please contact us.',
        ],
        'order_confirmation' => [
            'subject' => '['.config('app.name').'] Order Confirmation Code',
            'content' => 'Your order confirmation code is',
        ],
        'withdrawal_verification' => [
            'subject' => '['.config('app.name').'] Withdrawal Request - :time (UTC+8)',
            'greeting' => 'Withdrawal Requested',
            'content1' => 'Your account just issued a withdrawal request. Withdrawal information:',
            'content2' => "Before you confirm the withdrawal, please verify the target address carefully. If you confirm the withdrawal to an erroneous address, we won't be able to help recovering your assets. If you understand the risks and can confirm that this was your own action, please click the button below:",
            'action' => 'Confirm Withdrawal',
            'content3' => 'For the safety of your assets, the button link will expire after 30 minutes.',
        ],
        'withdrawal_bad_request_notification' => [
            'subject' => '['.config('app.name').'] Withdrawal Failure Notification - :time (UTC+8)',
            'greeting' => 'Your withdrawal has been canceled by system.',
            'content1' => 'Your withdrawal which was submitted at :confirmed_time (UTC+8) with following information:',
            'content2' => 'has been canceled by system at :canceled_time (UTC+8) due to invalid withdrawal information. The possible reasons may include invalid address or invalid tag provided.',
            'content3' => 'If you have any question about this withdrawal, please contact us.',
        ],
        'deal_notification' => [
            'subject' => '['.config('app.name').'] New Order #:order_id',
            'greeting' => 'New Order #:order_id',
            'content' => [
                'dst_user' => [
                    'buy' => 'You bought :amount :coin from :username.',
                    'sell' => ':username sold :amount :coin to you.',
                ],
                'src_user' => [
                    'buy' => ':username bought :amount :coin from you.',
                    'sell' => 'You sold :amount :coin to :username.',
                ]
            ],
            'action' => 'View Order',
            'dst_user_reminder' => 'You must complete your payment before :time (UTC+8), and click the "CONFIRM PAYMENT" button in the order page.'
        ],
        'claim_notification' => [
            'subject' => '['.config('app.name').'] Buyer Paid Order Notification #:order_id',
            'greeting' => 'Buyer Paid Order Notification #:order_id',
            'content' => 'The buyer of order #:order_id, :username claimed that he/she already paid. Please confirm that the payment has arrived and click the "CONFIRM" button in the order page.',
            'action' => 'View Order',
        ],
        'order_revoked_notification' => [
            'subject' => '['.config('app.name').'] Buyer Revoked Order Payment Notification #:order_id',
            'greeting' => 'Buyer Revoked Order Payment Notification #:order_id',
            'content' => 'The buyer of order #:order_id, :username has revoked his/her claim on paying this order.',
            'action' => 'View Order',
        ],
        'ad_unavailable_notification' => [
            'subject' => '['.config('app.name').'] Advertisement Unavailable Notification #:advertisement_id',
            'greeting' => 'Advertisement Unavailable Notification #:advertisement_id',
            'admin' => [
                'content' => 'Admin has pulled your advertisement #:advertisement_id from the shelf. Please contact us through our customer service if you have any further questions.',
            ],
            'system' => [
                'content' => 'System has automatically pulled your advertisement #:advertisement_id from the shelf since the value of cryptocurrency remainings in your advertisement were less than the minimum buying/selling limit. Please edit and activate your advertisement in order to make it available again.',
            ],
        ],
        'order_completed_dst_notification' => [
            'subject' => '['.config('app.name').'] Order Completed Notification #:order_id',
            'greeting' => 'Order Completed Notification #:order_id',
            'content' => 'Your payment of order #:order_id has been confirmed and the cryptocurrency has been transfered to your account.',
            'action' => 'View Order',
        ],
        'order_completed_src_notification' => [
            'subject' => '['.config('app.name').'] Order Completed Notification #:order_id',
            'greeting' => 'Order Completed Notification #:order_id',
            'content' => 'The payment of order #:order_id has been confirmed and the cryptocurrency has been transfered to the buyer\'s account.',
            'action' => 'View Order',
        ],
        'order_payment_check'=> [
            'subject' => '['.config('app.name').'] Order #:order_id payment requires your check',
            'greeting' => 'Order #:order_id payment requires your check',
            'content' => 'Order #:order_id \'s payment has been notified done by third party payment service, but the total is out of auto-lease range. Please check the payment and release it manually at the back stage.',
        ],
        'order_canceled_notification' => [
            'subject' => '['.config('app.name').'] Order Canceled Notification #:order_id',
            'greeting' => 'Order Canceled Notification #:order_id',
            'user' => [
                'seller' => ['content' => 'The buyer has canceled the order. Please contact us through our customer service if you have any further question.'],
                'buyer' => ['content' => 'Your order has been canceled successfully. Please contact us through our customer service if you have any further question.'],
            ],
            'system' => [
                'seller' => ['content' => 'System has canceled your order. The buyer did not claim the payment before this order payment expired. Please contact us through our customer service if you have any further question.'],
                'buyer' => ['content' => 'System has canceled your order. You did not claim this order before the payment expired. Please contact us through our customer service if you have any further question.'],
            ],
            'admin' => ['content' => 'Admin has canceled your order. Please contact us through our customer service if you have any further question.'],
        ],
        'auth_result_notificaiton' => [
            'guest' => 'Dear User',
            'status' => [
                'passed' => 'APPROVED',
                'rejected' => 'REJECTED',
            ],
            'subject' => '['.config('app.name').'] ID verification result notification',
            'greeting' => 'Dear :name',
            'result' => 'Your ID verification application is :status.',
            'explain' => 'We reject your application according to following reasons:',
            'follow_up' => [
                'passed' => 'All features are now enabled.',
                'rejected' => 'Please resubmit your ID verification application with valid information and files.',
            ],
            'action' => 'Visit '.config('app.name'),
        ],
        'bank_account_verification_notificaiton' => [
            'guest' => 'Dear User',
            'status' => [
                'approve' => 'APPROVED',
                'reject' => 'REJECTED',
            ],
            'subject' => '['.config('app.name').'] Bank account review result notification',
            'greeting' => 'Dear :name',
            'result' => [
                'approve' => 'We\'ve approved your bank account at :bank,',
                'reject' => 'We\'ve reviewed information of your bank account at :bank, and we are unable to approve this bank account.'
            ],
            'explain' => 'We reject your application according to following reasons:',
            'follow_up' => [
                'approve' => 'now you can use the bank account for trading.',
                'reject' => 'Please recreate your bank account with valid information.',
            ],
            'action' => 'Visit '.config('app.name'),
        ],
        'critical_error_alert' => [
            'subject' => '['.config('app.name').'] SYSTEM LOG - :time (UTC+8)',
        ],
        'login_fail_user_lock' => [
            'subject' => '['.config('app.name').'] User Lock By Login Failure Notification - :time (UTC+8)',
            'greeting' => 'Dear :name',
            'content' => 'We notice that your account failed to sign in from the same IP address :IP three times consecutively. We had blocked your account from signing in from this IP address for an hour.',
            'content2' => 'If you consider your account is in a security risk, please change your password as soon as possible.',
            'content3' => 'Please contact us through our customer service if you have any further question.',
        ],
        'security_code_fail_user_lock' => [
            'subject' => '['.config('app.name').'] User Lock By Security Code Failure Notification - :time (UTC+8)',
            'greeting' => 'Dear :name',
            'content' => 'We notice that your account enter the wrong security code for three times consecutively. We had blocked your account temporary for 24 hours.',
            'content2' => 'If you consider your account is in a security risk, please change your password as soon as possible.',
        ],
        'announcement_notification' => [
            'subject' => '['.config('app.name').'] :title - :time (UTC+8)',
            'greeting' => 'Dear :name',
            'content' => ':content',
            'action' => 'Visit '.config('app.name'),
        ],
    ],
    'push' => [
        'deal_notification' => [
            'subject' => '['.config('app.name').'] New Order #:order_id',
            'content' => 'You have a :amount :coin new order. Please check out the details in our application',
        ],
    ],
];
