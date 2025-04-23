<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MedAgenda - Sistema de Agendamento Médico</title>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                margin: 0;
                padding: 0;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                flex-direction: column;
            }
            .container {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .content {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 1rem;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                padding: 3rem;
                text-align: center;
                max-width: 32rem;
                width: 100%;
            }
            .logo {
                font-size: 2.5rem;
                font-weight: bold;
                color: #4f46e5;
                margin-bottom: 1.5rem;
            }
            .description {
                color: #4b5563;
                font-size: 1.125rem;
                line-height: 1.75;
                margin-bottom: 2rem;
            }
            .features {
                margin: 2rem 0;
                text-align: left;
            }
            .feature {
                display: flex;
                align-items: center;
                margin-bottom: 1rem;
                color: #4b5563;
            }
            .feature i {
                color: #4f46e5;
                margin-right: 1rem;
                font-size: 1.25rem;
            }
            .button {
                display: inline-flex;
                align-items: center;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.2s;
                background-color: #4f46e5;
                color: white;
            }
            .button:hover {
                background-color: #4338ca;
                transform: translateY(-1px);
            }
            .icon {
                margin-right: 0.5rem;
            }
            footer {
                text-align: center;
                padding: 1.5rem;
                background: rgba(255, 255, 255, 0.1);
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="logo">
                    <i class="fas fa-hospital-user icon"></i>
                    MedAgenda
                </div>
                <p class="description">
                    Bem-vindo ao sistema de agendamento médico mais eficiente do Brasil.
                </p>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-calendar-check"></i>
                        <span>Agendamento online 24/7</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-user-md"></i>
                        <span>Médicos especialistas qualificados</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <span>Consultas no horário marcado</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Lembretes por SMS e e-mail</span>
                    </div>
                </div>
                <a href="{{ route('login') }}" class="button">
                    <i class="fas fa-sign-in-alt icon"></i>
                    Acessar Sistema
                </a>
            </div>
        </div>
        <footer>
            &copy; {{ date('Y') }} MedAgenda. Todos os direitos reservados.
        </footer>
    </body>
</html>