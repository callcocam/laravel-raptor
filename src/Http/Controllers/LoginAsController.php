<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Http\Controllers;

use Illuminate\Http\Request;

class LoginAsController
{
    
    public function loginAs(Request $request)
    {

        $token = $request->query('token');

        if (!$token) {
            return redirect()->back()->withErrors('Token inválido para login como cliente.');
        }
     

        if ($user = \App\Models\User::find($token)) { 
            // Autentica o usuário no contexto do cliente
            auth()->login($user);
        } else {
            return redirect()->back()->withErrors('Usuário não encontrado para o token fornecido.');
        }
        // Lógica para autenticar como o cliente associado ao domínio fornecido
        // Exemplo: encontrar o cliente pelo domínio e autenticar
        // $client = Client::whereHas('domain', fn($query) => $query->where('domain', $domain))->first();
        // Auth::login($client->user);

        // Redirecionar para o painel do cliente
        return redirect()->route('dashboard');
    }
}
