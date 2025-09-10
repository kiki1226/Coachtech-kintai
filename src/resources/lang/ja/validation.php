<?php

return [

    // よく使うルール
    'required'  => ':attribute は必須です。',
    'email'     => 'メールアドレスの形式が正しくありません。',
    'unique'    => 'この :attribute は既に使用されています。',
    'confirmed' => ':attribute が確認用と一致しません。',
    'min'       => [
        'string' => ':attribute は :min 文字以上で入力してください。',
    ],

    // :attribute の日本語表示名
    'attributes' => [
        'name'                  => '名前',
        'email'                 => 'メールアドレス',
        'password'              => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
    ],
];
