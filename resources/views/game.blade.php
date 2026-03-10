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
                    <h3>Entrenador (opcional)</h3>
                    <div class="auth-box">
                        <input id="auth-name" class="input" type="text" placeholder="Nombre (registro)">
                        <input id="auth-email" class="input" type="email" placeholder="Email">
                        <input id="auth-password" class="input" type="password" placeholder="Contraseña">
                        <input id="auth-password-confirm" class="input" type="password" placeholder="Confirmar contraseña (registro)">
                        <div class="inline-grid">
                            <button id="auth-register-btn" class="btn" type="button">Registrarme</button>
                            <button id="auth-login-btn" class="btn" type="button">Entrar</button>
                        </div>
                        <a id="auth-google-btn" class="btn btn-warm" href="/auth/google/redirect">Entrar con Google</a>
                        <button id="auth-logout-btn" class="btn hidden" type="button">Cerrar sesión</button>
                        <div id="auth-status" class="muted">Modo invitado activo.</div>
                    </div>
                    <input id="profile-nickname" class="input" type="text" placeholder="Tu nombre de entrenador">
                    <div class="inline-grid">
                        <select id="profile-tier" class="input">
                            <option value="beginner">Principiante</option>
                            <option value="intermediate">Intermedio</option>
                            <option value="expert">Pro</option>
                        </select>
                        <select id="profile-avatar" class="input"></select>
                    </div>
                    <button id="profile-save-btn" class="btn" type="button">Guardar perfil</button>
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
