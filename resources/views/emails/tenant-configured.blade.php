<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Ambiente configurado') }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    @php
        $domain = $tenant->getAttribute('domain');
        $accessUrl = $domain ? 'https://' . $domain : null;
    @endphp
    @if($plainPassword)
        <h1>{{ __('Seu ambiente foi configurado') }}</h1>
        <p>{{ __('Olá,') }}</p>
        <p>{{ __('Foi criada uma conta de acesso para o tenant :name.', ['name' => $tenant->getAttribute('name')]) }}</p>
        @if($accessUrl)
            <p><strong>{{ __('Acesse em:') }}</strong> <a href="{{ $accessUrl }}">{{ $accessUrl }}</a></p>
        @endif
        <p><strong>{{ __('E-mail:') }}</strong> {{ $user->getAttribute('email') }}</p>
        <p><strong>{{ __('Senha temporária:') }}</strong> <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px;">{{ $plainPassword }}</code></p>
        <p>{{ __('Recomendamos alterar a senha no primeiro acesso.') }}</p>
    @else
        <h1>{{ __('Atualização do seu ambiente') }}</h1>
        <p>{{ __('Olá,') }}</p>
        <p>{{ __('O tenant :name foi atualizado.', ['name' => $tenant->getAttribute('name')]) }}</p>
        @if($accessUrl)
            <p><strong>{{ __('Acesse em:') }}</strong> <a href="{{ $accessUrl }}">{{ $accessUrl }}</a></p>
        @endif
        <p>{{ __('Use suas credenciais já cadastradas para acessar.') }}</p>
    @endif
    <p>{{ __('Atenciosamente,') }}<br>{{ config('app.name') }}</p>
</body>
</html>
