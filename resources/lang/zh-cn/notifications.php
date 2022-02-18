<?php

return [
    'sms' => [
        'verification' => '['.config('app.name').'] 验证码 :code.',
        'deal_notification' => '['.config('app.name').'] 订单成立通知 #:order_id，请登入查看订单资讯',
        'claim_notification' => '['.config('app.name').'] 买家已付款通知 #:order_id，请登入查看订单资讯',
        'reset_mobile_verification' => '['.config('app.name').'] 验证码 :code.',
    ],
    'email' => [
        'email_verification' => [
            'subject' => '['.config('app.name').'] Email 验证',
            'content' => '你的验证码是',
        ],
        'password_verification' => [
            'subject' => '['.config('app.name').'] 密码重置验证',
            'content' => '你的密码重置验证码是',
        ],
        'security_code_verification' => [
            'subject' => '['.config('app.name').'] 安全码重置验证',
            'content' => '你的安全码重置验证码是',
        ],
        'deactivate_tfa_verification' => [
            'subject' => '['.config('app.name').'] 停用双重认证验证',
            'content' => '你的停用双重认证验证码是',
        ],
        'reset_email_verification' => [
            'subject' => '['.config('app.name').'] Email 重置验证',
            'content' => '你的重置验证码是',
        ],
        'transfer_verification' => [
            'subject' => '['.config('app.name').'] 划转验证',
            'greeting' => '划转请求',
            'content1' => '你的帐号提出了划转请求，资讯如下:',
            'content2' => '在你确认划转请求之前，请仔细检查目的帐号，若你确认此提现为自己的操作，请点击下方按钮：',
            'action' => '确认划转',
            'content3' => '基于安全原因，此连结将于 30 分钟后失效。',
        ],
        'transfer_notification' => [
            'subject' => '['.config('app.name').'] 划转通知 - :time (UTC)',
            'greeting' => '划转已到帐!',
            'content1' => '您已于 :time (UTC) 收到来自 :source 的划转，数量 :amount :coin',
            'message' => ':source 有一则留言给您: :message',
            'content2' => '请前往交易明细查看更多相关细节，如果对此划转有疑虑或是任何问题，请联络我们',
        ],
        'deposit_notification' => [
            'subject' => '['.config('app.name').'] 充值成功通知 - :time (UTC)',
            'greeting' => '充值成功!',
            'content1' => '您已于:time (UTC)成功充值:amount :coin',
            'content2' => '请前往交易明细查看更多相关细节，如果对此充值有疑虑或是任何问题，请联络我们',
        ],
        'order_confirmation' => [
            'subject' => '['.config('app.name').'] 交易验证',
            'content' => '你的交易验证码是',
        ],
        'withdrawal_verification' => [
            'subject' => '['.config('app.name').'] 提现请求 - :time (UTC)',
            'greeting' => '提现请求',
            'content1' => '你的帐号提出了提现请求，资讯如下:',
            'content2' => '在你确认提现请求之前，请仔细检查目的地址与 Tag，若你提现至一个错误的地址，资产将无法取回。若你理解此风险并确认此提现为你自己所操作，请点击下方按钮：',
            'action' => '确认提现',
            'content3' => '基于安全原因，此连结将于 30 分钟后失效。',
        ],
        'withdrawal_bad_request_notification' => [
            'subject' => '['.config('app.name').'] 提现失败通知 - :time (UTC)',
            'greeting' => '您的提现已被系统取消',
            'content1' => '您于 :confirmed_time (UTC) 送出的提现：',
            'content2' => '因为资料有误，已于 :canceled_time (UTC) 被系统取消，可能是地址或是 tag 格式错误，请检查后重新操作。',
            'content3' => '如果对此有疑虑或是任何问题，请联络我们',
        ],
        'deal_notification' => [
            'subject' => '['.config('app.name').'] 订单成立通知 #:order_id',
            'greeting' => '订单成立通知 #:order_id',
            'content' => [
                'dst_user' => [
                    'buy' => '你向 :username 购买了 :amount :coin。',
                    'sell' => ':username 向你贩售了 :amount :coin。',
                ],
                'src_user' => [
                    'buy' => ':username 向你购买了 :amount :coin。',
                    'sell' => '你向 :username 贩售了 :amount :coin。',
                ]
            ],
            'action' => '查看订单',
            'dst_user_reminder' => '请于 :time UTC 之前完成付款，并点选订单中「确认已付款」按钮。'
        ],
        'claim_notification' => [
            'subject' => '['.config('app.name').'] 买家已付款通知 #:order_id',
            'greeting' => '买家已付款通知 #:order_id',
            'content' => '订单号 #:order_id 的买家 :username 表示已付款，请确认收到款项后，至订单页面点选「已确认」按钮。',
            'action' => '查看订单',
        ],
        'order_revoked_notification' => [
            'subject' => '['.config('app.name').'] 买家撤销已付款通知 #:order_id',
            'greeting' => '买家撤销已付款通知 #:order_id',
            'content' => '订单号 #:order_id 的买家 :username 已撤销宣称对此订单的付款。',
            'action' => '查看订单',
        ],
        'ad_unavailable_notification' => [
            'subject' => '['.config('app.name').'] 广告下架通知 #:advertisement_id',
            'greeting' => '广告下架通知 #:advertisement_id',
            'admin' => [
                'content' => '管理者已经将你的广告 #:advertisement_id 下架，如有任何问题，请透过客服管道联系我们。',
            ],
            'system' => [
                'content' => '由于你的广告中剩余加密货币的价值已小于最小单笔限额，系统已自动将你的广告 #:advertisement_id 下架，若要继续交易，请编辑广告内容后重新上架。',
            ],
        ],
        'order_completed_dst_notification' => [
            'subject' => '['.config('app.name').'] 订单完成通知 #:order_id',
            'greeting' => '订单完成通知 #:order_id',
            'content' => '订单号 #:order_id 的付款已确认，虚拟货币已放行至您的帐户中。',
            'action' => '查看订单',
        ],
        'order_completed_src_notification' => [
            'subject' => '['.config('app.name').'] 订单完成通知 #:order_id',
            'greeting' => '订单完成通知 #:order_id',
            'content' => '订单号 #:order_id 的付款已确认，虚拟货币已放行至买家的帐户中。',
            'action' => '查看订单',
        ],
        'order_payment_check'=> [
            'subject' => '['.config('app.name').'] 订单付款待确认通知 #:order_id',
            'greeting' => '订单付款待确认通知 #:order_id',
            'content' => '订单号 #:order_id 已收到第三方收到付款的通知，但因为付款范围在自动放行范围外，请确认收到付款后，至后台手动放行。',
        ],
        'order_canceled_notification' => [
            'subject' => '['.config('app.name').'] 订单取消通知 #:order_id',
            'greeting' => '订单取消通知 #:order_id',
            'user' => [
                'seller' => ['content' => '买家已经取消订单，如有任何问题，请透过客服管道联系我们。'],
                'buyer' => ['content' => '你的订单已经被取消，如有任何问题，请透过客服管道联系我们。'],
            ],
            'system' => [
                'seller' => ['content' => '因为订单已超过付款时限，但买家并未通知已付款，系统已将订单取消，如有任何问题，请透过客服管道联系我们。'],
                'buyer' => ['content' => '因为订单已超过付款时限，且未收到你的付款通知，系统已将订单取消，如有任何问题，请透过客服管道联系我们。'],
            ],
            'admin' => ['content' => '管理者已经取消你的订单，如有任何问题，请透过客服管道联系我们。'],
        ],
        'auth_result_notificaiton' => [
            'guest' => '亲爱的用户',
            'status' => [
                'passed' => '通过',
                'rejected' => '未通过',
            ],
            'subject' => '['.config('app.name').'] 身份验证:status通知',
            'greeting' => ':name您好',
            'result' => '您的身份验证结果为「:status」，',
            'explain' => '我们基于以下原因，无法通过您的身份验证：',
            'follow_up' => [
                'passed' => '从现在开始，您可以使用本网站的各种功能。',
                'rejected' => '请您备妥有效资料后，再次送出身份验证申请。',
            ],
            'action' => '前往 '.config('app.name'),
        ],
        'bank_account_verification_notificaiton' => [
            'guest' => '亲爱的用户',
            'status' => [
                'approve' => '通过',
                'rejecte' => '未通过',
            ],
            'subject' => '['.config('app.name').'] 银行帐号审核结果通知',
            'greeting' => ':name你好',
            'result' => [
                'approve' => '我们已通过你于 :bank 的银行帐号审核，',
                'reject' => '我们审核你了提供的 :bank 的银行帐号资料，但我们无法让你于 '. config('app.name').' 使用该银行帐号。'
            ],
            'explain' => '该银行帐号未通过审核的原因如下：',
            'follow_up' => [
                'approve' => '你现在已可使用这个银行帐号进行交易。',
                'reject' => '请重新使用正确的资料新增银行帐号。',
            ],
            'action' => '前往 '.config('app.name'),
        ],
        'login_fail_user_lock' => [
            'subject' => '['.config('app.name').'] 登入失败锁定通知 - :time (UTC)',
            'greeting' => '亲爱的用户 :name',
            'content' => '我们发现你的帐号连续登入失败达 3 次，我们已阻挡你的帐号从 IP 地址 :IP 登入，锁定时间 1 小时。',
            'content2' => '若您认为您的帐号有安全性风险，请尽速更换密码。',
            'content3' => '如有任何问题，请透过客服管道联系我们。',
        ],
        'security_code_fail_user_lock' => [
            'subject' => '['.config('app.name').'] 帐号锁定通知 - :time (UTC)',
            'greeting' => '亲爱的用户 :name',
            'content' => '我们发现你的帐号连续输入错误的安全码达 3 次，我们已暂时冻结你的帐号 24 小時。',
            'content2' => '若您认为您的帐号有安全性风险，请尽速联系我们。',
        ],
    ],
    'push' => [
        'deal_notification' => [
            'subject' => '['.config('app.name').'] 订单成立通知 #:order_id',
            'content' => ' 你有一笔:amount :coin的订单，请登入查看详情。',
        ],
    ],
];
