import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = 'http://localhost/helpdesk_prefeitura';

const usuario = {
    email: 'teste01@teste.com',
    senha: 'TesteCarga2026!'
};

export const options = {
  vus: 50,
  iterations: 50,
};

export default function () {

    // 1. LOGIN
    const loginResponse = http.post(
        `${BASE_URL}/account/login.php`,
        {
            email: usuario.email,
            senha: usuario.senha
        }
    );

    check(loginResponse, {
        'login respondeu': (r) => r.status === 200 || r.status === 302
    });

    // 2. ABRIR CHAMADO
    const chamadoResponse = http.post(
        `${BASE_URL}/usuario/abrir_chamado.php`,
        {
            tipo_solicitante: 'eu',
            titulo: 'TESTE K6 01',
            local: 'Sala de Teste',
            categoria_id: '1',
            prioridade: 'baixa',
            descricao: 'Chamado criado automaticamente pelo teste k6.'
        }
    );

    check(chamadoResponse, {
        'chamado respondeu': (r) => r.status === 200 || r.status === 302
    });
}