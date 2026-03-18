<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pokemon Who Is</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="bg-layer"></div>
    <div class="bg-grid"></div>

    <div class="app-shell">
        <header class="hero arcade-frame">
            <div class="hero-copy">
                <p class="eyebrow">Adivina Mi Pokemon</p>
                <h1>Pokemon Who Is</h1>
                <p class="subtitle">Modo fiesta para jugar en cel con amigos. Online por codigo, local y VS.</p>
                <div class="sync-row">
                    <span id="sync-status" class="muted">Pokedex cargada desde servidor.</span>
                </div>
                <div class="sync-row">
                    <select id="language-select" class="input language-select">
                        <option value="es">Español</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <div class="trainer-login">
                    <!-- Profile Card Display -->
                    <div id="profile-card" class="profile-card hidden">
                        <div class="profile-card-header">
                            <img id="profile-card-avatar" src="" alt="Avatar" class="profile-card-avatar">
                            <div class="profile-card-info">
                                <h2 id="profile-card-name" class="profile-card-name">Entrenador</h2>
                                <span id="profile-card-tier" class="profile-card-tier">Tier Principiante</span>
                            </div>
                        </div>
                        <div class="profile-card-stats">
                            <div class="stat-item">
                                <span class="stat-value" id="profile-card-level">1</span>
                                <span class="stat-label">Nivel</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="profile-card-wins">0</span>
                                <span class="stat-label">Victorias</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="profile-card-games">0</span>
                                <span class="stat-label">Partidas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="profile-card-xp">0</span>
                                <span class="stat-label">XP</span>
                            </div>
                        </div>
                        <button id="profile-edit-btn" class="btn-card-action" type="button">Editar Perfil</button>
                        <button id="profile-logout-btn" class="btn-card-action btn-card-logout hidden" type="button">Cerrar Sesión</button>
                    </div>

                    <!-- Auth Section -->
                    <div id="auth-section" class="auth-section">
                        <div class="auth-glass">
                            <div class="auth-header">
                                <div class="auth-pokeball-icon">
                                    <svg width="28" height="28" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="48" fill="none" stroke="white" stroke-width="4"/>
                                        <path d="M2 50 H98" stroke="white" stroke-width="4"/>
                                        <circle cx="50" cy="50" r="14" fill="none" stroke="white" stroke-width="4"/>
                                        <circle cx="50" cy="50" r="7" fill="white"/>
                                    </svg>
                                </div>
                                <h3 class="auth-title">Entrenador</h3>
                            </div>
                            <div id="auth-status" class="auth-status-badge">Modo invitado</div>

                            <div class="auth-fields">
                                <div id="auth-name-field" class="form-field hidden">
                                    <input id="auth-name" class="auth-input" type="text" placeholder="Nombre de entrenador">
                                </div>
                                <input id="auth-email" class="auth-input" type="email" placeholder="Email">
                                <input id="auth-password" class="auth-input" type="password" placeholder="Contraseña">
                                <div id="auth-password-confirm-field" class="form-field hidden">
                                    <input id="auth-password-confirm" class="auth-input" type="password" placeholder="Confirmar contraseña">
                                </div>
                            </div>

                            <button id="auth-login-btn" class="auth-btn auth-btn-primary" type="button">
                                <span>Entrar</span>
                            </button>
                            <button id="auth-register-btn" class="auth-btn auth-btn-primary hidden" type="button">
                                <span>Registrarme</span>
                            </button>

                            <div class="auth-or"><span>o</span></div>

                            <a id="auth-google-btn" class="auth-btn auth-btn-google" href="/auth/google/redirect">
                                <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                <span>Entrar con Google</span>
                            </a>

                            <p id="auth-login-toggle-text" class="auth-toggle-text">¿No tienes cuenta? <button id="auth-toggle-register" class="auth-link" type="button">Crear una</button></p>
                            <p id="auth-register-toggle-text" class="auth-toggle-text hidden">¿Ya tienes cuenta? <button id="auth-toggle-login" class="auth-link" type="button">Entrar</button></p>

                            <button id="auth-logout-btn" class="auth-btn auth-btn-logout hidden" type="button">Cerrar sesión</button>
                        </div>
                    </div>

                    <!-- Profile Edit Section -->
                    <div id="profile-edit-section" class="profile-edit-section hidden">
                        <div class="auth-glass">
                            <h3 class="auth-title" style="margin-bottom: 0.75rem;">Editar Perfil</h3>
                            <div class="auth-fields">
                                <input id="profile-nickname" class="auth-input" type="text" placeholder="Tu nombre de entrenador">
                                <select id="profile-tier" class="auth-input">
                                    <option value="beginner">Principiante</option>
                                    <option value="intermediate">Intermedio</option>
                                    <option value="expert">Pro</option>
                                </select>
                                <select id="profile-avatar" class="auth-input"></select>
                            </div>
                            <div class="profile-edit-actions">
                                <button id="profile-save-btn" class="auth-btn auth-btn-primary" type="button">Guardar</button>
                                <button id="profile-cancel-btn" class="auth-btn auth-btn-secondary" type="button">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sprite-stage" aria-hidden="true">
                <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png" alt="">
                <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/6.png" alt="">
                <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/94.png" alt="">
                <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/143.png" alt="">
                <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/150.png" alt="">
            </div>
        </header>

        <section class="panel profile-panel">
            <h2>Progreso de Entrenador</h2>
            <div id="profile-hud" class="muted">Aun no creas perfil. Puedes jugar sin login o guardar uno opcional.</div>
        </section>

        <nav class="mode-tabs arcade-frame" role="tablist">
            <button class="tab-btn active" data-mode="local" type="button">Local</button>
            <button class="tab-btn" data-mode="online" type="button">Online</button>
            <button class="tab-btn" data-mode="vs" type="button">VS</button>
            <button class="tab-btn" data-mode="allvsbot" type="button">All VS Bot</button>
            <button class="tab-btn" data-mode="pokedex" type="button">Pokédex</button>
            <button class="tab-btn" data-mode="user" type="button">Usuario</button>
        </nav>

        <main>
            <section class="panel mode-panel active" id="mode-local">
                <h2>Modo Local</h2>
                <p class="muted">Solo quien elige ve la ficha para responder sin errores.</p>
                <div class="inline-grid">
                    <input id="local-search" class="input" type="text" placeholder="Buscar Pokemon (ej. pikachu)">
                    <button id="local-search-btn" class="btn" type="button">Buscar</button>
                </div>
                <div id="local-list" class="list"></div>
                <div id="local-picked" class="pokemon-card hidden"></div>
                <h3>Preguntas rapidas (auto-evaluadas)</h3>
                <div class="inline-grid">
                    <select id="local-question-select" class="input"></select>
                    <button id="local-evaluate-btn" class="btn" type="button">Evaluar</button>
                </div>
                <div id="local-answer" class="answer-badge">Aun sin evaluar</div>
            </section>

            <section class="panel mode-panel" id="mode-online">
                <h2>Modo Online</h2>
                <p class="muted">Crea o unete con codigo. Facil: descarte automatico. Normal: historial.</p>
                <div class="room-forms">
                    <div class="sub-panel">
                        <h3>Crear sala</h3>
                        <input id="online-nickname" class="input" type="text" placeholder="Tu nombre">
                        <input id="online-room-name" class="input" type="text" placeholder="Nombre de sala (opcional)">
                        <select id="online-difficulty" class="input">
                            <option value="easy">Facil</option>
                            <option value="normal">Normal</option>
                        </select>
                        <select id="online-visibility" class="input">
                            <option value="private">Privada (codigo)</option>
                            <option value="public">Publica (global)</option>
                        </select>
                        <button id="online-create-btn" class="btn" type="button">Crear</button>
                    </div>
                    <div class="sub-panel">
                        <h3>Unirse</h3>
                        <input id="online-code" class="input" type="text" maxlength="6" placeholder="Codigo">
                        <input id="online-join-nickname" class="input" type="text" placeholder="Tu nombre">
                        <button id="online-join-btn" class="btn" type="button">Unirse</button>
                    </div>
                </div>
                <div class="sub-panel" style="margin-top: .85rem;">
                    <h3>Salas publicas globales</h3>
                    <button id="online-refresh-public-btn" class="btn" type="button">Actualizar lista</button>
                    <div id="online-public-list" class="list"></div>
                </div>
                <div id="online-state" class="room-state hidden"></div>
            </section>

            <section class="panel mode-panel" id="mode-vs">
                <h2>Modo VS</h2>
                <p class="muted">Duelo por turnos. Facil muestra descarte. Dificil solo historial.</p>
                <div class="room-forms">
                    <div class="sub-panel">
                        <h3>Crear VS</h3>
                        <input id="vs-nickname" class="input" type="text" placeholder="Tu nombre">
                        <input id="vs-room-name" class="input" type="text" placeholder="Nombre de sala (opcional)">
                        <select id="vs-difficulty" class="input">
                            <option value="easy">Facil</option>
                            <option value="hard">Dificil</option>
                        </select>
                        <select id="vs-visibility" class="input">
                            <option value="private">Privada (codigo)</option>
                            <option value="public">Publica (global)</option>
                        </select>
                        <button id="vs-create-btn" class="btn" type="button">Crear</button>
                    </div>
                    <div class="sub-panel">
                        <h3>Unirse VS</h3>
                        <input id="vs-code" class="input" type="text" maxlength="6" placeholder="Codigo">
                        <input id="vs-join-nickname" class="input" type="text" placeholder="Tu nombre">
                        <button id="vs-join-btn" class="btn" type="button">Unirse</button>
                    </div>
                </div>
                <div class="sub-panel" style="margin-top: .85rem;">
                    <h3>Salas VS publicas globales</h3>
                    <button id="vs-refresh-public-btn" class="btn" type="button">Actualizar lista</button>
                    <div id="vs-public-list" class="list"></div>
                </div>
                <div id="vs-state" class="room-state hidden"></div>
            </section>

            <section class="panel mode-panel" id="mode-allvsbot">
                <h2>Modo All VS Bot</h2>
                <p class="muted">Hasta 4 jugadores contra un Pokémon aleatorio. Turnos rotativos y límite de preguntas por jugador.</p>
                <div class="room-forms">
                    <div class="sub-panel">
                        <h3>Crear All VS Bot</h3>
                        <input id="allvsbot-nickname" class="input" type="text" placeholder="Tu nombre">
                        <input id="allvsbot-room-name" class="input" type="text" placeholder="Nombre de sala (opcional)">
                        <select id="allvsbot-difficulty" class="input">
                            <option value="easy">Fácil</option>
                            <option value="normal">Normal</option>
                        </select>
                        <select id="allvsbot-question-limit" class="input">
                            <option value="2">2 preguntas por jugador</option>
                            <option value="3" selected>3 preguntas por jugador</option>
                            <option value="4">4 preguntas por jugador</option>
                            <option value="5">5 preguntas por jugador</option>
                        </select>
                        <select id="allvsbot-visibility" class="input">
                            <option value="private">Privada (código)</option>
                            <option value="public">Pública (global)</option>
                        </select>
                        <button id="allvsbot-create-btn" class="btn" type="button">Crear</button>
                    </div>
                    <div class="sub-panel">
                        <h3>Unirse All VS Bot</h3>
                        <input id="allvsbot-code" class="input" type="text" maxlength="6" placeholder="Código">
                        <input id="allvsbot-join-nickname" class="input" type="text" placeholder="Tu nombre">
                        <button id="allvsbot-join-btn" class="btn" type="button">Unirse</button>
                    </div>
                </div>
                <div class="sub-panel" style="margin-top: .85rem;">
                    <h3>Salas All VS Bot públicas</h3>
                    <button id="allvsbot-refresh-public-btn" class="btn" type="button">Actualizar lista</button>
                    <div id="allvsbot-public-list" class="list"></div>
                </div>
                <div id="allvsbot-state" class="room-state hidden"></div>
            </section>

            <section class="panel mode-panel" id="mode-pokedex">
                <h2>Pokédex Completa</h2>
                <p class="muted">Todos los Pokémon disponibles ordenados por número de Pokédex.</p>
                <div class="inline-grid" style="margin-bottom: .5rem;">
                    <input id="pokedex-search" class="input" type="text" placeholder="Buscar nombre o #número">
                    <select id="pokedex-gen-filter" class="input">
                        <option value="">Todas las gen.</option>
                        <option value="1">Gen 1</option>
                        <option value="2">Gen 2</option>
                        <option value="3">Gen 3</option>
                        <option value="4">Gen 4</option>
                        <option value="5">Gen 5</option>
                        <option value="6">Gen 6</option>
                        <option value="7">Gen 7</option>
                        <option value="8">Gen 8</option>
                        <option value="9">Gen 9</option>
                    </select>
                    <select id="pokedex-type-filter" class="input">
                        <option value="">Todos los tipos</option>
                    </select>
                </div>
                <div id="pokedex-count" class="muted" style="margin-bottom: .5rem;"></div>
                <div id="pokedex-grid" class="pokedex-grid"></div>
                <div style="text-align:center; margin-top: .75rem;">
                    <button id="pokedex-load-more" class="btn" type="button">Cargar más</button>
                </div>
                <div id="pokedex-detail" class="pokemon-detail-modal hidden"></div>
            </section>

            <section class="panel mode-panel" id="mode-user">
                <h2>Vista de Usuario y Logros</h2>
                <p class="muted">
                    Explicación simple: juega partidas, pregunta, responde y adivina Pokémon. Cada reto completado te da un trofeo
                    y desbloquea un Pokémon sorpresa como recompensa de tu colección.
                </p>
                <div class="sub-panel" style="margin-top:.75rem;">
                    <h3>Gachapón de Niveles y Tiers</h3>
                    <p class="muted">
                        Cada nivel te da una cápsula aleatoria. Si subes de tier, recibes cápsula especial con garantía mítica o legendaria.
                    </p>
                    <div id="gacha-summary" class="answer-badge">Cápsulas pendientes: 0</div>
                    <button id="gacha-open-btn" class="btn btn-warm" type="button">Abrir cápsula</button>
                    <div id="gacha-wheel" class="gacha-wheel"></div>
                    <div id="gacha-result" class="muted">Sin apertura todavía.</div>
                </div>
                <div id="achievements-summary" class="answer-badge">Sin progreso todavía.</div>
                <div id="achievements-grid" class="achievements-grid"></div>
                <div class="sub-panel" style="margin-top:.75rem;">
                    <h3>Pokémon Obtenidos</h3>
                    <div id="collection-summary" class="answer-badge">Aún no tienes Pokémon en tu colección.</div>
                    <div id="collection-grid" class="pokedex-grid"></div>
                </div>
            </section>
        </main>
    </div>

    <div id="gacha-cinematic" class="gacha-cinematic hidden">
        <div class="gacha-cinematic-card">
            <div class="gacha-cinematic-top">
                <h2 id="gacha-cinematic-title">¡Apertura Gachapón!</h2>
                <button id="gacha-cinematic-close" class="btn" type="button">Cerrar</button>
            </div>
            <div class="gacha-cinematic-wheel" id="gacha-cinematic-wheel"></div>
            <div class="gacha-cinematic-reveal" id="gacha-cinematic-reveal"></div>
        </div>
    </div>
</body>
</html>
