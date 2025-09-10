@component('mail::message')
# {{ $user->name }} 様

この度はご登録ありがとうございます。  
下のボタンからメールアドレスの確認を完了してください。

@component('mail::button', ['url' => $url])
認証はこちら
@endcomponent

※このリンクは60分で失効します。期限切れの場合は、画面の「認証メールを再送する」から再度お試しください。  

— COACHTECH 勤怠サポート
@endcomponent
