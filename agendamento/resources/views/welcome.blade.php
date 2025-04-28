<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MedAgenda - Sistema de Agendamento Médico Inteligente">
    <title>MedAgenda - Seu Agendamento Médico Simplificado</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #60a5fa;
            --secondary: #0ea5e9;
            --accent: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            background-color: var(--gray-50);
        }

        .hero {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            min-height: 600px;
            padding: 4rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: url('https://images.pexels.com/photos/3846035/pexels-photo-3846035.jpeg?auto=compress&cs=tinysrgb&w=1920') center/cover;
            opacity: 0.1;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            color: white;
            text-align: center;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            max-width: 600px;
            margin: 0 auto 3rem;
            opacity: 0.9;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: -100px auto 0;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-700);
            font-size: 1.1rem;
        }

        .features {
            padding: 8rem 2rem 4rem;
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            padding: 2rem;
            border-radius: 1rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: white;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 1.5rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .feature-description {
            color: var(--gray-700);
            line-height: 1.6;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            padding: 6rem 2rem;
            text-align: center;
            color: white;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .cta-description {
            font-size: 1.25rem;
            max-width: 600px;
            margin: 0 auto 2rem;
            opacity: 0.9;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            padding: 1rem 2.5rem;
            font-size: 1.125rem;
            font-weight: 600;
            color: white;
            background: var(--primary);
            border-radius: 0.75rem;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .cta-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .testimonials {
            padding: 6rem 2rem;
            background: var(--gray-50);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .testimonial-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .testimonial-content {
            font-size: 1.1rem;
            color: var(--gray-700);
            margin-bottom: 1.5rem;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .author-info h4 {
            font-weight: 600;
            color: var(--gray-900);
        }

        .author-info p {
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        footer {
            background: var(--gray-900);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: white;
            text-decoration: none;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-link {
            color: var(--gray-200);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: white;
        }

        .copyright {
            color: var(--gray-200);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .stats-container {
                margin-top: -50px;
            }

            .cta-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Seu bem-estar é nossa prioridade</h1>
            <p class="hero-subtitle">Agende consultas médicas de forma simples e rápida, com os melhores profissionais da sua região.</p>
            <a href="{{ route('login') }}" class="cta-button">Começar Agora</a>
        </div>
    </section>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-number">50.000+</div>
            <div class="stat-label">Pacientes Atendidos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">1.000+</div>
            <div class="stat-label">Médicos Parceiros</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">98%</div>
            <div class="stat-label">Satisfação dos Pacientes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Suporte Disponível</div>
        </div>
    </div>

    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3 class="feature-title">Agendamento Simplificado</h3>
                <p class="feature-description">Marque suas consultas em poucos cliques, escolhendo o melhor horário para você.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👨‍⚕️</div>
                <h3 class="feature-title">Especialistas Qualificados</h3>
                <p class="feature-description">Acesso aos melhores profissionais de saúde, com avaliações e comentários de pacientes.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Lembretes Automáticos</h3>
                <p class="feature-description">Receba notificações sobre suas consultas por SMS e e-mail, nunca perca um compromisso.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💊</div>
                <h3 class="feature-title">Histórico Médico Digital</h3>
                <p class="feature-description">Mantenha seu histórico médico organizado e acessível a qualquer momento.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏥</div>
                <h3 class="feature-title">Rede Credenciada</h3>
                <p class="feature-description">Ampla rede de clínicas e hospitais parceiros em todo o Brasil.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💳</div>
                <h3 class="feature-title">Pagamento Facilitado</h3>
                <p class="feature-description">Diversas formas de pagamento aceitas, com total segurança e praticidade.</p>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <p class="testimonial-content">"O MedAgenda revolucionou a forma como marco minhas consultas. Muito prático e eficiente!"</p>
                <div class="testimonial-author">
                    <img src="https://images.pexels.com/photos/733872/pexels-photo-733872.jpeg?auto=compress&cs=tinysrgb&w=100" alt="Maria Silva" class="author-image">
                    <div class="author-info">
                        <h4>Maria Silva</h4>
                        <p>Paciente desde 2023</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p class="testimonial-content">"Como médico, o sistema me ajuda a organizar melhor minha agenda e atender mais pacientes."</p>
                <div class="testimonial-author">
                    <img src="https://images.pexels.com/photos/5452293/pexels-photo-5452293.jpeg?auto=compress&cs=tinysrgb&w=100" alt="Dr. Carlos Santos" class="author-image">
                    <div class="author-info">
                        <h4>Dr. Carlos Santos</h4>
                        <p>Cardiologista</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p class="testimonial-content">"Os lembretes automáticos são ótimos, nunca mais perdi uma consulta!"</p>
                <div class="testimonial-author">
                    <img src="https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?auto=compress&cs=tinysrgb&w=100" alt="Ana Oliveira" class="author-image">
                    <div class="author-info">
                        <h4>Ana Oliveira</h4>
                        <p>Paciente desde 2024</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <h2 class="cta-title">Comece a cuidar da sua saúde hoje mesmo</h2>
        <p class="cta-description">Junte-se a milhares de pessoas que já descobriram uma nova forma de cuidar da saúde.</p>
        <a href="{{ route('login') }}" class="cta-button">Acessar Sistema</a>
    </section>

    <footer>
        <div class="footer-content">
            <a href="/" class="footer-logo">MedAgenda</a>
            <div class="footer-links">
                <a href="#" class="footer-link">Sobre Nós</a>
                <a href="#" class="footer-link">Contato</a>
                <a href="#" class="footer-link">Termos de Uso</a>
                <a href="#" class="footer-link">Privacidade</a>
            </div>
            <p class="copyright">&copy; {{ date('Y') }} MedAgenda. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>