import './bootstrap';

const apiBase = '/api';
const storageKey = 'pwi_player_token';
const langKey = 'pwi_lang';
const authTokenKey = 'pwi_auth_token';
const onlineRoomKey = 'pwi_online_room';
const vsRoomKey = 'pwi_vs_room';

const i18n = {
    es: {
        syncing: 'Sincronizando...',
        syncError: 'Error leyendo estado inicial',
        loaded: 'Pokemon cargados',
        hiddenReady: 'Pokemon oculto listo. Evalua preguntas abajo.',
        selectFirst: 'Primero selecciona un Pokemon.',
        answerYes: 'Respuesta: SI',
        answerNo: 'Respuesta: NO',
        answerUnknown: 'Respuesta: No aplica',
        guessed: 'Adivinaste!',
        notGuessed: 'No era ese Pokemon.',
        noPublicRooms: 'No hay salas publicas disponibles.',
        roomTypePublic: 'Publica',
        roomTypePrivate: 'Privada',
        legendary: 'Legendario',
        mythical: 'Mitico',
        me: 'tu',
        joinNeedName: 'Pon tu nombre para entrar.',
        guestMode: 'Modo invitado activo.',
        loggedAs: 'Sesión iniciada como',
        profileSaved: 'Perfil guardado.',
        noProfileYet: 'Aun no creas perfil. Puedes jugar sin login o guardar uno opcional.',
        level: 'Nivel',
        wins: 'Victorias',
        games: 'Partidas',
        xpNext: 'XP para siguiente nivel',
        authErrorSocialiteMissing: 'Login con Google no disponible en servidor (falta Socialite).',
        authErrorGoogleRedirect: 'No se pudo iniciar login con Google.',
        authErrorGoogleCallback: 'Google devolvió error al finalizar login.',
    },
    en: {
        syncing: 'Syncing...',
        syncError: 'Error loading initial state',
        loaded: 'Pokemon loaded',
        hiddenReady: 'Hidden Pokemon ready. Evaluate questions below.',
        selectFirst: 'Select a Pokemon first.',
        answerYes: 'Answer: YES',
        answerNo: 'Answer: NO',
        answerUnknown: 'Answer: Not applicable',
        guessed: 'You guessed it!',
        notGuessed: 'That was not the Pokemon.',
        noPublicRooms: 'No public rooms available.',
        roomTypePublic: 'Public',
        roomTypePrivate: 'Private',
        legendary: 'Legendary',
        mythical: 'Mythical',
        me: 'you',
        joinNeedName: 'Set your name before joining.',
        guestMode: 'Guest mode active.',
        loggedAs: 'Signed in as',
        profileSaved: 'Profile saved.',
        noProfileYet: 'No profile yet. You can play as guest or save an optional profile.',
        level: 'Level',
        wins: 'Wins',
        games: 'Games',
        xpNext: 'XP for next level',
        authErrorSocialiteMissing: 'Google login is not available on server (Socialite missing).',
        authErrorGoogleRedirect: 'Could not start Google login.',
        authErrorGoogleCallback: 'Google returned an error while finishing login.',
    },
};

const state = {
    playerToken: localStorage.getItem(storageKey) || '',
    authToken: localStorage.getItem(authTokenKey) || '',
    authUser: null,
    language: localStorage.getItem(langKey) || 'es',
    localPokemon: null,
    localQuestions: {},
    typeLabels: {},
    onlineRoomCode: localStorage.getItem(onlineRoomKey) || '',
    onlineRoom: null,
    vsRoomCode: localStorage.getItem(vsRoomKey) || '',
    vsRoom: null,
    profile: null,
    avatarCatalog: {},
    achievements: null,
    gacha: null,
};

const syncStatus = document.getElementById('sync-status');
const languageSelect = document.getElementById('language-select');
const authNameInput = document.getElementById('auth-name');
const authEmailInput = document.getElementById('auth-email');
const authPasswordInput = document.getElementById('auth-password');
const authPasswordConfirmInput = document.getElementById('auth-password-confirm');
const authRegisterBtn = document.getElementById('auth-register-btn');
const authLoginBtn = document.getElementById('auth-login-btn');
const authGoogleBtn = document.getElementById('auth-google-btn');
const authLogoutBtn = document.getElementById('auth-logout-btn');
const profileLogoutBtn = document.getElementById('profile-logout-btn');
const authStatus = document.getElementById('auth-status');
const authSection = document.getElementById('auth-section');
const authNameField = document.getElementById('auth-name-field');
const authPasswordConfirmField = document.getElementById('auth-password-confirm-field');
const authLoginToggleText = document.getElementById('auth-login-toggle-text');
const authRegisterToggleText = document.getElementById('auth-register-toggle-text');
const authToggleRegisterBtn = document.getElementById('auth-toggle-register');
const authToggleLoginBtn = document.getElementById('auth-toggle-login');
const authFields = [
    authNameInput,
    authEmailInput,
    authPasswordInput,
    authPasswordConfirmInput,
    authRegisterBtn,
    authLoginBtn,
    authGoogleBtn,
].filter(Boolean);

const profileCard = document.getElementById('profile-card');
const profileCardName = document.getElementById('profile-card-name');
const profileCardTier = document.getElementById('profile-card-tier');
const profileCardLevel = document.getElementById('profile-card-level');
const profileCardWins = document.getElementById('profile-card-wins');
const profileCardGames = document.getElementById('profile-card-games');
const profileCardXp = document.getElementById('profile-card-xp');
const profileCardAvatar = document.getElementById('profile-card-avatar');
const profileEditBtn = document.getElementById('profile-edit-btn');
const profileEditSection = document.getElementById('profile-edit-section');
const profileCancelBtn = document.getElementById('profile-cancel-btn');
const profileNicknameInput = document.getElementById('profile-nickname');
const profileTierSelect = document.getElementById('profile-tier');
const profileAvatarSelect = document.getElementById('profile-avatar');
const profileSaveBtn = document.getElementById('profile-save-btn');
const profileHud = document.getElementById('profile-hud');
const achievementsSummary = document.getElementById('achievements-summary');
const achievementsGrid = document.getElementById('achievements-grid');
const gachaSummary = document.getElementById('gacha-summary');
const gachaOpenBtn = document.getElementById('gacha-open-btn');
const gachaWheel = document.getElementById('gacha-wheel');
const gachaResult = document.getElementById('gacha-result');
const gachaCinematic = document.getElementById('gacha-cinematic');
const gachaCinematicClose = document.getElementById('gacha-cinematic-close');
const gachaCinematicWheel = document.getElementById('gacha-cinematic-wheel');
const gachaCinematicReveal = document.getElementById('gacha-cinematic-reveal');
const gachaCinematicTitle = document.getElementById('gacha-cinematic-title');
const gachaCinematicCard = gachaCinematic?.querySelector('.gacha-cinematic-card');

const localSearchInput = document.getElementById('local-search');
const localSearchBtn = document.getElementById('local-search-btn');
const localList = document.getElementById('local-list');
const localPicked = document.getElementById('local-picked');
const localQuestionSelect = document.getElementById('local-question-select');
const localEvaluateBtn = document.getElementById('local-evaluate-btn');
const localAnswer = document.getElementById('local-answer');

const onlineStateBox = document.getElementById('online-state');
const vsStateBox = document.getElementById('vs-state');
const onlinePublicRoomsList = document.getElementById('online-public-list');
const refreshOnlinePublicBtn = document.getElementById('online-refresh-public-btn');
const vsPublicRoomsList = document.getElementById('vs-public-list');
const refreshVsPublicBtn = document.getElementById('vs-refresh-public-btn');

const tabButtons = Array.from(document.querySelectorAll('.tab-btn'));
const modePanels = Array.from(document.querySelectorAll('.mode-panel'));

for (const tab of tabButtons) {
    tab.addEventListener('click', () => {
        const mode = tab.dataset.mode;
        tabButtons.forEach((button) => button.classList.toggle('active', button === tab));
        modePanels.forEach((panel) => panel.classList.toggle('active', panel.id === `mode-${mode}`));
    });
}

function t(key) {
    return i18n[state.language][key] || key;
}

function localPokemonSprite(pokeapiId) {
    return pokeapiId ? `/sprites/pokemon/${pokeapiId}.png` : '';
}

function pokemonSpriteUrl(pokemon) {
    return pokemon?.sprite
        || pokemon?.sprites?.official_artwork
        || pokemon?.sprites?.front_default
        || localPokemonSprite(pokemon?.pokeapi_id)
        || '';
}

function pokeballSpriteUrl() {
    return (state.gacha?.ball_catalog && state.gacha.ball_catalog['poke-ball'])
        || '/sprites/items/poke-ball.png';
}

function debounce(fn, wait = 280) {
    let timer = null;
    return (...args) => {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(() => fn(...args), wait);
    };
}

function isUserTyping() {
    const active = document.activeElement;
    if (!active) {
        return false;
    }

    const tag = active.tagName;
    return (
        tag === 'INPUT' ||
        tag === 'TEXTAREA' ||
        tag === 'SELECT' ||
        active.isContentEditable
    );
}

async function api(path, options = {}) {
    const url = `${apiBase}${path}`;
    const config = {
        method: options.method || 'GET',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
    };

    if (state.authToken) {
        config.headers.Authorization = `Bearer ${state.authToken}`;
    }

    if (options.data) {
        config.body = JSON.stringify(options.data);
    }

    const response = await fetch(url, config);
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload.message || payload.error || 'API Error');
    }

    return payload;
}

function setPlayerToken(token) {
    if (!token) {
        return;
    }

    state.playerToken = token;
    localStorage.setItem(storageKey, token);
    updateGoogleAuthLink();
}

function setAuthToken(token) {
    state.authToken = token || '';
    if (state.authToken) {
        localStorage.setItem(authTokenKey, state.authToken);
    } else {
        localStorage.removeItem(authTokenKey);
    }
}

function updateGoogleAuthLink() {
    const params = new URLSearchParams();
    if (state.playerToken) {
        params.set('player_token', state.playerToken);
    }
    authGoogleBtn.href = `/auth/google/redirect${params.toString() ? `?${params.toString()}` : ''}`;
}

function renderAuthStatus() {
    if (!state.authUser) {
        authStatus.textContent = t('guestMode');
        authLogoutBtn.classList.add('hidden');
        profileLogoutBtn?.classList.add('hidden');
        authFields.forEach((element) => element.classList.remove('hidden'));
        // Show auth-or divider and google btn
        const authOr = document.querySelector('.auth-or');
        if (authOr) authOr.style.display = '';
        authGoogleBtn?.classList.remove('hidden');
        // Show toggle text
        authLoginToggleText?.classList.remove('hidden');
        return;
    }

    authStatus.textContent = `${t('loggedAs')}: ${state.authUser.name} (${state.authUser.email})`;
    authLogoutBtn.classList.remove('hidden');
    profileLogoutBtn?.classList.remove('hidden');
    authFields.forEach((element) => element.classList.add('hidden'));
    // Hide divider and toggle when logged in
    const authOr = document.querySelector('.auth-or');
    if (authOr) authOr.style.display = 'none';
    authLoginToggleText?.classList.add('hidden');
    authRegisterToggleText?.classList.add('hidden');
}

async function loadAuthMe() {
    if (!state.authToken) {
        state.authUser = null;
        renderAuthStatus();
        return;
    }

    try {
        const payload = await api('/auth/me', { auth: true });
        state.authUser = payload.user || null;
        renderAuthStatus();
    } catch (error) {
        setAuthToken('');
        state.authUser = null;
        renderAuthStatus();
    }
}

async function registerAuth() {
    const payload = await api('/auth/register', {
        method: 'POST',
        data: {
            name: authNameInput.value,
            email: authEmailInput.value,
            password: authPasswordInput.value,
            password_confirmation: authPasswordConfirmInput.value,
            player_token: state.playerToken || null,
        },
    });

    setAuthToken(payload.auth_token);
    state.authUser = payload.user;
    renderAuthStatus();
    await loadProfile();
    renderProfileHud();
}

async function loginAuth() {
    const payload = await api('/auth/login', {
        method: 'POST',
        data: {
            email: authEmailInput.value,
            password: authPasswordInput.value,
            player_token: state.playerToken || null,
        },
    });

    setAuthToken(payload.auth_token);
    state.authUser = payload.user;
    renderAuthStatus();
    await loadProfile();
    renderProfileHud();
}

async function logoutAuth() {
    try {
        await api('/auth/logout', { method: 'POST', auth: true });
    } catch (error) {
        // noop
    }

    setAuthToken('');
    state.authUser = null;
    state.achievements = null;
    state.gacha = null;
    renderAuthStatus();
    renderAchievements();
    renderGacha();
}

function consumeAuthFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('auth_token');
    const authName = params.get('auth_name');
    const authEmail = params.get('auth_email');
    const authError = params.get('auth_error');

    if (authError) {
        const map = {
            socialite_missing: t('authErrorSocialiteMissing'),
            google_redirect_failed: t('authErrorGoogleRedirect'),
            google_callback_failed: t('authErrorGoogleCallback'),
        };
        authStatus.textContent = map[authError] || authError;
    }

    if (!token) {
        if (authError) {
            params.delete('auth_error');
            const cleanUrl = `${window.location.pathname}${params.toString() ? `?${params}` : ''}`;
            window.history.replaceState({}, document.title, cleanUrl);
        }
        return;
    }

    setAuthToken(token);
    state.authUser = {
        name: authName || 'Trainer',
        email: authEmail || '',
    };
    renderAuthStatus();

    if (!profileNicknameInput.value && authName) {
        profileNicknameInput.value = authName;
    }

    params.delete('auth_token');
    params.delete('auth_name');
    params.delete('auth_email');
    params.delete('auth_error');
    const cleanUrl = `${window.location.pathname}${params.toString() ? `?${params}` : ''}`;
    window.history.replaceState({}, document.title, cleanUrl);
}

function setLanguage(lang) {
    state.language = lang === 'en' ? 'en' : 'es';
    localStorage.setItem(langKey, state.language);
    languageSelect.value = state.language;
}

function formatType(type) {
    return state.typeLabels[type] || type;
}

function renderAvatarOptions() {
    const catalog = state.avatarCatalog || {};
    const options = Object.entries(catalog)
        .map(([key]) => `<option value="${key}">${key}</option>`)
        .join('');
    profileAvatarSelect.innerHTML = options || '<option value="trainer-a">trainer-a</option>';

    if (state.profile?.avatar_key) {
        profileAvatarSelect.value = state.profile.avatar_key;
    }
}

function autoFillNicknames() {
    const nickname = state.profile?.nickname || state.authUser?.name
        || `Invitado-${Math.floor(Math.random() * 9000000000 + 1000000000)}`;
    ['online-nickname', 'vs-nickname', 'online-join-nickname', 'vs-join-nickname'].forEach((id) => {
        const el = document.getElementById(id);
        if (el && !el.value) {
            el.value = nickname;
        }
    });
}

function renderProfileHud() {
    autoFillNicknames();
    if (!state.profile) {
        profileHud.textContent = t('noProfileYet');
        return;
    }

    const avatar = state.avatarCatalog[state.profile.avatar_key] || '';
    const tier = state.achievements?.summary?.tier_name || 'Bronce';
    const tierCode = state.achievements?.summary?.tier_code || 'bronze';
    profileHud.innerHTML = `
        <div class="profile-hud-row">
            <img class="profile-avatar" src="${avatar}" alt="avatar">
            <div>
                <strong>${state.profile.nickname || 'Trainer'}</strong>
                <div class="muted">${t('level')} ${state.profile.level} · ${state.profile.experience_tier}</div>
                <div class="muted">${t('wins')}: ${state.profile.wins} · ${t('games')}: ${state.profile.games_played}</div>
                <span class="tier-pill tier-${tierCode}">Tier ${tier}</span>
            </div>
        </div>
        <div class="xp-wrap">
            <div class="muted">XP: ${state.profile.xp} · ${t('xpNext')}: ${state.profile.next_level_xp}</div>
            <div class="xp-track"><div class="xp-fill" style="width:${state.profile.level_progress_percent}%"></div></div>
        </div>
    `;
    
    // Update profile card
    updateProfileCard();
}

function updateProfileCard() {
    if (!state.profile) {
        profileCard?.classList.add('hidden');
        authSection?.classList.remove('hidden');
        profileEditSection?.classList.add('hidden');
        return;
    }

    const avatar = state.avatarCatalog[state.profile.avatar_key] || '';
    const tier = state.achievements?.summary?.tier_name || 'Principiante';
    
    // Update card display
    profileCardName.textContent = state.profile.nickname || 'Trainer';
    profileCardTier.textContent = `Tier ${tier}`;
    profileCardLevel.textContent = state.profile.level || '1';
    profileCardWins.textContent = state.profile.wins || '0';
    profileCardGames.textContent = state.profile.games_played || '0';
    profileCardXp.textContent = state.profile.xp || '0';
    profileCardAvatar.src = avatar;
    profileCardAvatar.alt = state.profile.nickname || 'Trainer';
    
    // Show card, hide auth section
    profileCard?.classList.remove('hidden');
    authSection?.classList.add('hidden');
    profileEditSection?.classList.add('hidden');
}

function renderAchievements() {
    const achievements = state.achievements;
    if (!achievements) {
        achievementsSummary.textContent = 'Crea/guarda tu perfil para empezar a desbloquear logros.';
        achievementsGrid.innerHTML = '';
        return;
    }

    achievementsSummary.innerHTML = `Logros desbloqueados: ${achievements.summary.unlocked}/${achievements.summary.total} (${achievements.summary.completion_percent}%) · <span class="tier-pill tier-${achievements.summary.tier_code}">Tier ${achievements.summary.tier_name}</span>`;

    achievementsGrid.innerHTML = achievements.items.map((item) => {
        const unlocked = item.is_unlocked;
        const reward = item.reward;
        const icon = reward?.sprite || pokeballSpriteUrl();
        const progress = Math.max(0, Math.min(100, item.progress_percent || 0));

        return `
            <article class="achievement-card ${unlocked ? '' : 'locked'}">
                <div class="achievement-icon">
                    <img src="${icon}" alt="${item.title}">
                </div>
                <div>
                    <strong>${item.title}</strong>
                    <div class="muted">${item.description}</div>
                    <div class="muted">${item.current}/${item.target} · ${unlocked ? 'Completado' : 'En progreso'}</div>
                    <div class="progress-mini"><span style="width:${progress}%"></span></div>
                    ${reward ? `<div class="pill">Recompensa: #${reward.pokeapi_id} ${reward.display_name}</div>` : ''}
                </div>
            </article>
        `;
    }).join('');
}

async function loadAchievements() {
    if (!state.playerToken) {
        state.achievements = null;
        renderAchievements();
        return;
    }

    const payload = await api(`/achievements?player_token=${encodeURIComponent(state.playerToken)}`);
    state.achievements = payload.achievements;
    renderAchievements();
    if (state.profile) {
        renderProfileHud();
    }
}

function rarityLabel(rarity) {
    const map = {
        normal: 'Normal',
        rare: 'Raro',
        special: 'Especial',
        ultra: 'Ultra',
        mythic: 'Mítico',
        legendary: 'Legendario',
    };

    return map[rarity] || rarity;
}

function renderGacha() {
    const gacha = state.gacha;
    if (!gacha) {
        gachaSummary.textContent = 'Cápsulas pendientes: 0';
        gachaOpenBtn.disabled = true;
        gachaWheel.innerHTML = '<div class=\"muted\">Sin cápsulas todavía.</div>';
        gachaResult.textContent = 'Sin apertura todavía.';
        return;
    }

    gachaSummary.textContent = `Cápsulas pendientes: ${gacha.pending_count}`;
    gachaOpenBtn.disabled = gacha.pending_count < 1;

    const slots = [
        'poke-ball',
        'poke-ball',
        'great-ball',
        'ultra-ball',
        'poke-ball',
        'cherish-ball',
        'master-ball',
    ];
    const ballCatalog = gacha.ball_catalog || {};
    gachaWheel.innerHTML = `<div class=\"gacha-track\">${slots.map((ball, idx) => `<div class=\"gacha-slot ${idx === 3 ? 'active' : ''}\"><img src=\"${ballCatalog[ball] || ballCatalog['poke-ball'] || ''}\" alt=\"${ball}\"></div>`).join('')}</div>`;
}

async function loadGacha() {
    if (!state.playerToken) {
        state.gacha = null;
        renderGacha();
        return;
    }

    const payload = await api(`/gacha?player_token=${encodeURIComponent(state.playerToken)}`);
    state.gacha = payload.gacha;
    renderGacha();
}

function closeGachaCinematic() {
    if (!gachaCinematic) {
        console.warn('gachaCinematic element not available');
        return;
    }
    gachaCinematic.classList.add('hidden');
    gachaCinematic.style.display = 'none';
    gachaCinematicReveal.innerHTML = '';
    if (gachaCinematicCard) {
        gachaCinematicCard.classList.remove('theme-normal', 'theme-rare', 'theme-special', 'theme-ultra', 'theme-mythic', 'theme-legendary', 'cinematic-shake');
        gachaCinematicCard.querySelector('.gacha-spark-burst')?.remove();
    }
}

function openGachaCinematic() {
    if (!gachaCinematic || !state.playerToken) {
        return;
    }
    gachaCinematic.classList.remove('hidden');
    gachaCinematic.style.display = 'grid';
    gachaCinematicReveal.innerHTML = '<div class="muted">Preparando cápsula...</div>';
}

function gachaThemeClass(rarity) {
    const map = {
        normal: 'theme-normal',
        rare: 'theme-rare',
        special: 'theme-special',
        ultra: 'theme-ultra',
        mythic: 'theme-mythic',
        legendary: 'theme-legendary',
    };

    return map[rarity] || 'theme-normal';
}

function playGachaTone(rarity) {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) {
            return;
        }

        const ctx = new AudioCtx();
        const sequence = {
            normal: [330, 392],
            rare: [392, 494, 523],
            special: [440, 523, 659],
            ultra: [494, 659, 784],
            mythic: [523, 659, 784, 988],
            legendary: [440, 659, 880, 1175],
        }[rarity] || [330, 392];

        const start = ctx.currentTime;
        sequence.forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.value = freq;
            gain.gain.value = 0.0001;
            osc.connect(gain);
            gain.connect(ctx.destination);
            const t = start + i * 0.08;
            gain.gain.exponentialRampToValueAtTime(0.055, t + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.09);
            osc.start(t);
            osc.stop(t + 0.1);
        });
    } catch (error) {
        // no-op
    }
}

function vibrateByRarity(rarity) {
    if (!navigator.vibrate) {
        return;
    }

    const pattern = {
        normal: [30],
        rare: [40, 20, 40],
        special: [55, 25, 55],
        ultra: [65, 30, 65, 25, 45],
        mythic: [75, 30, 75, 30, 75],
        legendary: [90, 35, 90, 35, 90],
    }[rarity] || [30];

    navigator.vibrate(pattern);
}

function sparkColorByRarity(rarity) {
    return {
        normal: '#cbd5e1',
        rare: '#60a5fa',
        special: '#818cf8',
        ultra: '#fbbf24',
        mythic: '#e879f9',
        legendary: '#f87171',
    }[rarity] || '#cbd5e1';
}

function spawnGachaBurst(rarity) {
    if (!gachaCinematicCard) {
        return;
    }

    const burst = document.createElement('div');
    burst.className = 'gacha-spark-burst';
    const color = sparkColorByRarity(rarity);

    for (let i = 0; i < 22; i++) {
        const spark = document.createElement('span');
        spark.className = 'gacha-spark';
        const angle = (Math.PI * 2 * i) / 22;
        const distance = 55 + Math.random() * 120;
        spark.style.setProperty('--sx', `${Math.cos(angle) * distance}px`);
        spark.style.setProperty('--sy', `${Math.sin(angle) * distance}px`);
        spark.style.left = `${48 + (Math.random() * 8 - 4)}%`;
        spark.style.top = `${46 + (Math.random() * 8 - 4)}%`;
        spark.style.background = color;
        spark.style.animationDelay = `${Math.random() * 90}ms`;
        burst.appendChild(spark);
    }

    gachaCinematicCard.appendChild(burst);
    setTimeout(() => burst.remove(), 1100);
}

async function animateGachaOpen(reward) {
    const gacha = state.gacha || {};
    const ballCatalog = gacha.ball_catalog || {};
    const pool = Object.values(ballCatalog);
    if (!pool.length) {
        return;
    }

    openGachaCinematic();
    gachaCinematicTitle.textContent = reward.source === 'tier_up'
        ? '¡Cápsula de Tier! Premio Garantizado'
        : '¡Cápsula de Nivel!';

    const slots = Array.from({ length: 7 }, (_, idx) => `
        <div class=\"gacha-slot ${idx === 3 ? 'active' : ''}\">
            <img src=\"${pool[Math.floor(Math.random() * pool.length)]}\" alt=\"ball\">
        </div>
    `).join('');
    gachaCinematicWheel.innerHTML = `<div class=\"gacha-track\">${slots}</div>`;

    const center = () => gachaCinematicWheel.querySelectorAll('.gacha-slot img')[3];

    for (let i = 0; i < 24; i++) {
        const delay = i < 12 ? 52 : (i < 19 ? 92 : 140);
        await new Promise((resolve) => setTimeout(resolve, delay));
        const img = center();
        if (img) {
            img.src = pool[Math.floor(Math.random() * pool.length)];
        }
    }

    const finalImg = center();
    if (finalImg) {
        finalImg.src = reward.ball_sprite;
    }

    if (gachaCinematicCard) {
        gachaCinematicCard.classList.remove('theme-normal', 'theme-rare', 'theme-special', 'theme-ultra', 'theme-mythic', 'theme-legendary');
        gachaCinematicCard.classList.add(gachaThemeClass(reward.rarity), 'cinematic-shake');
        setTimeout(() => gachaCinematicCard.classList.remove('cinematic-shake'), 460);
    }
    spawnGachaBurst(reward.rarity);
    playGachaTone(reward.rarity);
    vibrateByRarity(reward.rarity);

    gachaCinematicReveal.innerHTML = `
        <strong>¡Te salió #${reward.pokemon.pokeapi_id} ${reward.pokemon.display_name}!</strong>
        <span class=\"rarity-pill rarity-${reward.rarity}\">${rarityLabel(reward.rarity)}</span>
        <div class=\"muted\">Ball: ${reward.ball_type} · Fuente: ${reward.source === 'tier_up' ? 'Tier Up' : 'Subida de nivel'}</div>
        <div class=\"pokemon-card\" style=\"margin-top:.45rem;\">
            <img src=\"${reward.pokemon?.sprite || pokemonSpriteUrl(reward.pokemon)}\" alt=\"${reward.pokemon.display_name}\">
            <div>
                <strong>#${reward.pokemon.pokeapi_id} ${reward.pokemon.display_name}</strong>
                <div class=\"muted\">${reward.pokemon.primary_type || ''}${reward.pokemon.secondary_type ? ` / ${reward.pokemon.secondary_type}` : ''}</div>
            </div>
        </div>
    `;

    gachaResult.innerHTML = `
        <strong>¡Te salió #${reward.pokemon.pokeapi_id} ${reward.pokemon.display_name}!</strong>
        <span class=\"rarity-pill rarity-${reward.rarity}\">${rarityLabel(reward.rarity)}</span>
    `;
}

async function openGacha() {
    if (!state.playerToken) {
        return;
    }

    gachaOpenBtn.disabled = true;
    try {
        const payload = await api('/gacha/open', {
            method: 'POST',
            data: {
                player_token: state.playerToken,
            },
        });

        await animateGachaOpen(payload.reward);
        state.gacha = payload.gacha;
        renderGacha();
        await loadAchievements();
    } catch (error) {
        gachaResult.textContent = error.message;
    } finally {
        if ((state.gacha?.pending_count || 0) > 0) {
            gachaOpenBtn.disabled = false;
        }
    }
}

async function saveProfile() {
    const response = await api('/profile/upsert', {
        method: 'POST',
        data: {
            player_token: state.playerToken || null,
            nickname: profileNicknameInput.value || null,
            experience_tier: profileTierSelect.value,
            avatar_key: profileAvatarSelect.value || null,
        },
    });

    setPlayerToken(response.player_token);
    state.profile = response.profile;
    state.avatarCatalog = response.avatar_catalog || {};
    renderAvatarOptions();
    renderProfileHud();
    await loadAchievements();
    await loadGacha();
    syncStatus.textContent = t('profileSaved');
}

async function loadProfile() {
    if (!state.playerToken && !state.authToken) {
        return;
    }

    const url = state.playerToken
        ? `/profile?player_token=${encodeURIComponent(state.playerToken)}`
        : '/profile';
    const payload = await api(url);

    if (payload.player_token) {
        setPlayerToken(payload.player_token);
    }

    state.profile = payload.profile;
    state.avatarCatalog = payload.avatar_catalog || state.avatarCatalog;
    renderAvatarOptions();

    if (state.profile) {
        profileNicknameInput.value = state.profile.nickname || '';
        profileTierSelect.value = state.profile.experience_tier || 'beginner';
        profileAvatarSelect.value = state.profile.avatar_key || 'trainer-a';
    }

    renderProfileHud();
    await loadAchievements();
    await loadGacha();
}

function pokemonCardHtml(pokemon) {
    const sprite = pokemonSpriteUrl(pokemon);
    const height = pokemon.height_dm != null ? (pokemon.height_dm / 10).toFixed(1) : '?';
    const weight = pokemon.weight_hg != null ? (pokemon.weight_hg / 10).toFixed(1) : '?';
    const abilities = (pokemon.abilities || []).join(', ') || '—';
    const stats = pokemon.stats || {};
    const totalStats = Object.values(stats).reduce((a, b) => a + b, 0);

    return `
        <img src="${sprite}" alt="${pokemon.display_name}">
        <div>
            <strong>#${pokemon.pokeapi_id} ${pokemon.display_name}</strong>
            <div class="muted">Gen ${pokemon.generation || '?'} | ${formatType(pokemon.primary_type)}${pokemon.secondary_type ? ` / ${formatType(pokemon.secondary_type)}` : ''}</div>
            <div class="muted">Altura: ${height} m | Peso: ${weight} kg</div>
            <div class="muted">Habilidades: ${abilities}</div>
            <div class="muted">HP: ${stats.hp ?? '?'} | Atk: ${stats.attack ?? '?'} | Def: ${stats.defense ?? '?'} | SpA: ${stats['special-attack'] ?? '?'} | SpD: ${stats['special-defense'] ?? '?'} | Spe: ${stats.speed ?? '?'} | Total: ${totalStats}</div>
            <div class="muted">Exp base: ${pokemon.base_experience ?? '?'}</div>
            <div>
                ${pokemon.is_legendary ? `<span class="pill">${t('legendary')}</span>` : ''}
                ${pokemon.is_mythical ? `<span class="pill">${t('mythical')}</span>` : ''}
                ${pokemon.is_baby ? '<span class="pill">Bebé</span>' : ''}
            </div>
        </div>
    `;
}

function searchResultButtonHtml(pokemon, action, textPrefix = '') {
    const sprite = pokemonSpriteUrl(pokemon);
    return `<button type="button" class="search-item" data-action="${action}" data-pokemon-id="${pokemon.id}">
        <img src="${sprite}" alt="${pokemon.display_name}">
        <span>${textPrefix}#${pokemon.pokeapi_id} ${pokemon.display_name}</span>
    </button>`;
}

async function loadQuestionCatalog() {
    const data = await api(`/pokemon/questions?lang=${state.language}`);
    state.localQuestions = data.questions || {};
    state.typeLabels = data.type_labels || {};

    localQuestionSelect.innerHTML = Object.entries(state.localQuestions)
        .map(([key, value]) => {
            const label = value[`label_${state.language}`] || value.label;
            return `<option value="${key}">${label}</option>`;
        })
        .join('');
}

async function searchPokemon(term, limit = 20) {
    const q = term.trim();
    const url = q ? `/pokemon?search=${encodeURIComponent(q)}&limit=${limit}` : `/pokemon?limit=${limit}`;
    const data = await api(url);
    return data.data || [];
}

// ── Pokédex browser ──
const pokedexGrid = document.getElementById('pokedex-grid');
const pokedexSearch = document.getElementById('pokedex-search');
const pokedexGenFilter = document.getElementById('pokedex-gen-filter');
const pokedexTypeFilter = document.getElementById('pokedex-type-filter');
const pokedexLoadMore = document.getElementById('pokedex-load-more');
const pokedexCount = document.getElementById('pokedex-count');
const pokedexDetail = document.getElementById('pokedex-detail');
let pokedexOffset = 0;
const pokedexPageSize = 60;

function pokedexCompactCardHtml(pokemon) {
    const sprite = pokemonSpriteUrl(pokemon);
    return `<button type="button" class="pokedex-card" data-pokemon-detail='${JSON.stringify(pokemon).replace(/'/g, '&#39;')}'>
        <img src="${sprite}" alt="${pokemon.display_name}" loading="lazy">
        <span class="pokedex-card-num">#${pokemon.pokeapi_id}</span>
        <span class="pokedex-card-name">${pokemon.display_name}</span>
    </button>`;
}

function buildPokedexQuery() {
    const params = new URLSearchParams();
    const search = pokedexSearch.value.trim();
    if (search) params.set('search', search);
    const gen = pokedexGenFilter.value;
    if (gen) params.set('generation', gen);
    const type = pokedexTypeFilter.value;
    if (type) params.set('type', type);
    params.set('limit', String(pokedexPageSize));
    return params;
}

async function loadPokedexPage(append = false) {
    const params = buildPokedexQuery();
    params.set('offset', String(pokedexOffset));
    const data = await api(`/pokemon?${params.toString()}`);
    const list = data.data || [];

    if (!append) {
        pokedexGrid.innerHTML = '';
    }

    pokedexGrid.insertAdjacentHTML('beforeend', list.map(pokedexCompactCardHtml).join(''));
    pokedexCount.textContent = `Mostrando ${pokedexGrid.children.length} de ${data.total_loaded} Pokémon`;
    pokedexLoadMore.classList.toggle('hidden', list.length < pokedexPageSize);
    bindPokedexDetailClicks();
}

function bindPokedexDetailClicks() {
    pokedexGrid.querySelectorAll('.pokedex-card').forEach((btn) => {
        btn.onclick = () => {
            const pokemon = JSON.parse(btn.dataset.pokemonDetail);
            showPokedexDetail(pokemon);
        };
    });
}

function showPokedexDetail(pokemon) {
    pokedexDetail.classList.remove('hidden');
    pokedexDetail.innerHTML = `
        <div class="pokedex-detail-inner">
            <button type="button" class="pokedex-detail-close" id="pokedex-detail-close">&times;</button>
            <div class="pokemon-card">${pokemonCardHtml(pokemon)}</div>
        </div>
    `;
    document.getElementById('pokedex-detail-close').addEventListener('click', () => {
        pokedexDetail.classList.add('hidden');
    });
}

function populateTypeFilter() {
    const types = ['normal','fire','water','grass','electric','ice','fighting','poison','ground','flying','psychic','bug','rock','ghost','dragon','dark','steel','fairy'];
    pokedexTypeFilter.innerHTML = '<option value="">Todos los tipos</option>' +
        types.map((t) => `<option value="${t}">${formatType(t)}</option>`).join('');
}

pokedexSearch.addEventListener('input', debounce(() => {
    pokedexOffset = 0;
    loadPokedexPage();
}, 300));

pokedexGenFilter.addEventListener('change', () => {
    pokedexOffset = 0;
    loadPokedexPage();
});

pokedexTypeFilter.addEventListener('change', () => {
    pokedexOffset = 0;
    loadPokedexPage();
});

pokedexLoadMore.addEventListener('click', () => {
    pokedexOffset += pokedexPageSize;
    loadPokedexPage(true);
});

async function handleLocalSearch() {
    const found = await searchPokemon(localSearchInput.value, 24);

    localList.innerHTML = found.map((pokemon) => searchResultButtonHtml(pokemon, 'local-pick')).join('');

    localList.querySelectorAll('[data-action="local-pick"]').forEach((button) => {
        button.addEventListener('click', async () => {
            const pokemonId = button.dataset.pokemonId;
            const response = await api(`/pokemon/${pokemonId}`);
            state.localPokemon = response.data;
            localPicked.classList.remove('hidden');
            localPicked.innerHTML = pokemonCardHtml(state.localPokemon);
            localAnswer.textContent = t('hiddenReady');
        });
    });
}

async function evaluateLocalQuestion() {
    if (!state.localPokemon) {
        localAnswer.textContent = t('selectFirst');
        return;
    }

    const payload = await api('/pokemon/evaluate', {
        method: 'POST',
        data: {
            pokemon_id: state.localPokemon.id,
            question_key: localQuestionSelect.value,
        },
    });

    const answer = payload.answer || 'unknown';
    if (answer === 'yes') {
        localAnswer.innerHTML = `<span class="good">${t('answerYes')}</span>`;
    } else if (answer === 'no') {
        localAnswer.innerHTML = `<span class="bad">${t('answerNo')}</span>`;
    } else {
        localAnswer.textContent = t('answerUnknown');
    }
}

function roomStateHtml(room) {
    const me = room.players.find((player) => player.is_me);
    const amTurn = room.turn_session_id && me && room.turn_session_id === me.session_id;

    if (room.my_profile) {
        state.profile = {
            ...state.profile,
            ...room.my_profile,
        };
        renderProfileHud();
    }

    const playersHtml = room.players
        .map((player) => {
            const marker = player.is_me ? ` (${t('me')})` : '';
            const hidden = player.has_hidden_pokemon ? 'Pokemon listo' : 'Sin Pokemon';
            return `<span class="pill">${player.nickname}${marker} - ${player.role} - ${hidden}</span>`;
        })
        .join(' ');

    const pendingQuestions = room.questions.filter((question) => question.is_pending_for_me);

    const pendingHtml = pendingQuestions.length
        ? `<div><h3>Respuestas pendientes</h3>${pendingQuestions
              .map(
                  (question) => `<div class="history-item">
                        <div><strong>${question.asked_by_name}</strong>: ${question.question_text}</div>
                        <div class="answer-actions">
                            <button class="btn" type="button" data-action="answer" data-question-id="${question.id}" data-value="yes">Si</button>
                            <button class="btn" type="button" data-action="answer" data-question-id="${question.id}" data-value="no">No</button>
                            <button class="btn" type="button" data-action="answer" data-question-id="${question.id}" data-value="unknown">No se</button>
                        </div>
                    </div>`
              )
              .join('')}</div>`
        : '';

    const questionOptions = Object.entries(room.question_catalog || {})
        .map(([key, q]) => {
            const label = q[`label_${state.language}`] || q.label;
            return `<option value="${key}">${label}</option>`;
        })
        .join('');

    const historyHtml = room.questions
        .map((question) => {
            const answer = question.answer ? question.answer.toUpperCase() : 'PENDIENTE';
            return `<div class="history-item">
                <strong>${question.asked_by_name}</strong>: ${question.question_text} <span class="muted">(${answer})</span>
            </div>`;
        })
        .join('');

    const remaining = room.remaining_hint;
    const remainingHtml = remaining
        ? `<div>
            <h3>Descarte automatico</h3>
            <p class="muted">Quedan ${remaining.count} candidatos (mostrando hasta 60).</p>
            <div>${remaining.items.map((item) => `<span class="pill">#${item.pokeapi_id} ${item.display_name}</span>`).join('')}</div>
        </div>`
        : '';

    const winnerHtml = room.status === 'finished' && room.winner_session_id
        ? `<p><strong>Ganador:</strong> ${room.players.find((player) => player.session_id === room.winner_session_id)?.nickname || 'Jugador'}${room.surrendered_by ? ' (por rendición)' : ''}</p>`
        : '';

    // Timer display
    const timer = room.timer || {};
    let timerHtml = '';
    if (timer.enabled && room.status !== 'finished') {
        const fmtTime = (s) => { const m = Math.floor(s / 60); const sec = s % 60; return `${m}:${sec.toString().padStart(2, '0')}`; };
        timerHtml = `<div class="room-timer">
            <span class="timer-clock ${timer.my_remaining <= 30 ? 'timer-danger' : ''}">⏱ Tú: ${fmtTime(timer.my_remaining ?? 0)}</span>
            <span class="timer-clock ${timer.opponent_remaining <= 30 ? 'timer-danger' : ''}">⏱ Rival: ${fmtTime(timer.opponent_remaining ?? 0)}</span>
        </div>`;
    }

    // Timer proposal
    let timerProposalHtml = '';
    if (room.status !== 'finished' && !timer.enabled) {
        if (timer.proposed_by && timer.proposed_by !== me?.session_id) {
            timerProposalHtml = `<div class="timer-proposal">
                <p><strong>${timer.proposed_by_name}</strong> propone activar reloj (3 min por jugador)</p>
                <button class="btn" type="button" data-action="timer-accept">Aceptar</button>
                <button class="btn" type="button" data-action="timer-reject">Rechazar</button>
            </div>`;
        } else if (timer.proposed_by && timer.proposed_by === me?.session_id) {
            timerProposalHtml = `<p class="muted">Esperando que el rival acepte el reloj...</p>`;
        } else {
            timerProposalHtml = `<button class="btn btn-sm" type="button" data-action="timer-propose" title="Proponer reloj de 3 min por jugador">⏱ Proponer reloj</button>`;
        }
    }

    // Surrender
    const surrenderHtml = room.status !== 'finished' && me
        ? `<button class="btn btn-danger" type="button" data-action="surrender">🏳️ Rendirse</button>`
        : '';

    return `
        <div>
            <p><strong>Codigo:</strong> ${room.code} | <strong>Modo:</strong> ${room.mode} | <strong>Dificultad:</strong> ${room.difficulty}</p>
            <p><strong>Visibilidad:</strong> ${room.visibility === 'public' ? t('roomTypePublic') : t('roomTypePrivate')} | <strong>Idioma:</strong> ${room.language.toUpperCase()}</p>
            ${room.room_name ? `<p><strong>Sala:</strong> ${room.room_name}</p>` : ''}
            <p><strong>Estado:</strong> ${room.status}${amTurn ? ' | Te toca' : ''}</p>
            ${winnerHtml}
            ${timerHtml}
            ${timerProposalHtml}
            <p class="muted">Pokemon cargados en tu base: ${room.pokedex_loaded}</p>
            <div>${playersHtml}</div>

            <div style="margin-top: 1rem;">
                <h3>Elegir Pokemon oculto</h3>
                <div class="inline-grid">
                    <input class="input" id="room-hidden-search" type="text" placeholder="Buscar Pokemon o #">
                    <button class="btn" type="button" data-action="search-hidden">Buscar</button>
                </div>
                <div id="room-hidden-results" class="list"></div>
                ${me?.hidden_pokemon ? `<div class="pokemon-card">${pokemonCardHtml(me.hidden_pokemon)}</div>` : ''}
            </div>

            <div style="margin-top: 1rem;">
                <h3>Hacer pregunta</h3>
                <select id="room-question-key" class="input">${questionOptions}</select>
                <div class="inline-grid">
                    <button class="btn" type="button" data-action="ask-key">Pregunta estructurada</button>
                </div>
                <input id="room-question-text" class="input" type="text" placeholder="Pregunta libre (opcional)">
                <button class="btn" type="button" data-action="ask-free" style="margin-top: .5rem;">Enviar libre</button>
            </div>

            <div style="margin-top: 1rem;">
                <h3>Adivinar Pokemon</h3>
                <div class="inline-grid">
                    <input class="input" id="room-guess-search" type="text" placeholder="Buscar para adivinar">
                    <button class="btn" type="button" data-action="search-guess">Buscar</button>
                </div>
                <div id="room-guess-results" class="list"></div>
            </div>

            ${pendingHtml}
            ${remainingHtml}

            <div class="history">
                <h3>Historial</h3>
                ${historyHtml || '<p class="muted">Aun no hay preguntas.</p>'}
            </div>

            ${surrenderHtml}
        </div>
    `;
}

function attachPokemonResultActions(container, action, callback) {
    container.querySelectorAll(`[data-action="${action}"]`).forEach((button) => {
        button.addEventListener('click', () => callback(Number(button.dataset.pokemonId)));
    });
}

function bindRoomEvents(mode, room) {
    const container = mode === 'online' ? onlineStateBox : vsStateBox;

    const refreshRoom = async () => {
        await loadRoom(mode, room.code);
    };

    const hiddenInput = container.querySelector('#room-hidden-search');
    const hiddenResults = container.querySelector('#room-hidden-results');
    const guessInput = container.querySelector('#room-guess-search');
    const guessResults = container.querySelector('#room-guess-results');

    const renderHiddenSearch = async () => {
        const list = await searchPokemon(hiddenInput.value, 18);
        hiddenResults.innerHTML = list.map((pokemon) => searchResultButtonHtml(pokemon, 'pick-hidden')).join('');
        attachPokemonResultActions(hiddenResults, 'pick-hidden', async (pokemonId) => {
            try {
                await api(`/rooms/${room.code}/select-hidden`, {
                    method: 'POST',
                    data: {
                        player_token: state.playerToken,
                        pokemon_id: pokemonId,
                    },
                });
                await refreshRoom();
            } catch (error) {
                alert(error.message);
            }
        });
    };

    const renderGuessSearch = async () => {
        const list = await searchPokemon(guessInput.value, 18);
        guessResults.innerHTML = list.map((pokemon) => searchResultButtonHtml(pokemon, 'do-guess', 'Adivinar ')).join('');
        attachPokemonResultActions(guessResults, 'do-guess', async (pokemonId) => {
            try {
                const result = await api(`/rooms/${room.code}/guess`, {
                    method: 'POST',
                    data: {
                        player_token: state.playerToken,
                        pokemon_id: pokemonId,
                    },
                });

                alert(result.correct ? t('guessed') : t('notGuessed'));
                await refreshRoom();
            } catch (error) {
                alert(error.message);
            }
        });
    };

    hiddenInput?.addEventListener('input', debounce(renderHiddenSearch, 250));
    guessInput?.addEventListener('input', debounce(renderGuessSearch, 250));

    container.querySelector('[data-action="search-hidden"]')?.addEventListener('click', renderHiddenSearch);
    container.querySelector('[data-action="search-guess"]')?.addEventListener('click', renderGuessSearch);

    container.querySelector('[data-action="ask-key"]')?.addEventListener('click', async () => {
        try {
            const key = container.querySelector('#room-question-key').value;
            await api(`/rooms/${room.code}/ask`, {
                method: 'POST',
                data: {
                    player_token: state.playerToken,
                    question_key: key,
                    lang: state.language,
                },
            });
            await refreshRoom();
        } catch (error) {
            alert(error.message);
        }
    });

    container.querySelector('[data-action="ask-free"]')?.addEventListener('click', async () => {
        try {
            const text = container.querySelector('#room-question-text').value;
            await api(`/rooms/${room.code}/ask`, {
                method: 'POST',
                data: {
                    player_token: state.playerToken,
                    question_text: text,
                    lang: state.language,
                },
            });
            await refreshRoom();
        } catch (error) {
            alert(error.message);
        }
    });

    container.querySelectorAll('[data-action="answer"]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await api(`/rooms/${room.code}/answer`, {
                    method: 'POST',
                    data: {
                        player_token: state.playerToken,
                        question_id: Number(button.dataset.questionId),
                        answer: button.dataset.value,
                    },
                });
                await refreshRoom();
            } catch (error) {
                alert(error.message);
            }
        });
    });

    container.querySelector('[data-action="surrender"]')?.addEventListener('click', async () => {
        if (!confirm('¿Seguro que quieres rendirte? Perderás la partida.')) return;
        try {
            await api(`/rooms/${room.code}/surrender`, {
                method: 'POST',
                data: { player_token: state.playerToken },
            });
            await refreshRoom();
        } catch (error) {
            alert(error.message);
        }
    });

    container.querySelector('[data-action="timer-propose"]')?.addEventListener('click', async () => {
        try {
            await api(`/rooms/${room.code}/timer-propose`, {
                method: 'POST',
                data: { player_token: state.playerToken },
            });
            await refreshRoom();
        } catch (error) {
            alert(error.message);
        }
    });

    container.querySelector('[data-action="timer-accept"]')?.addEventListener('click', async () => {
        try {
            await api(`/rooms/${room.code}/timer-accept`, {
                method: 'POST',
                data: { player_token: state.playerToken, accept: true },
            });
            await refreshRoom();
        } catch (error) {
            alert(error.message);
        }
    });

    container.querySelector('[data-action="timer-reject"]')?.addEventListener('click', async () => {
        try {
            await api(`/rooms/${room.code}/timer-accept`, {
                method: 'POST',
                data: { player_token: state.playerToken, accept: false },
            });
            await refreshRoom();
        } catch (error) {
            alert(error.message);
        }
    });
}

async function renderRoom(mode, room) {
    const container = mode === 'online' ? onlineStateBox : vsStateBox;
    container.classList.remove('hidden');
    container.innerHTML = roomStateHtml(room);
    bindRoomEvents(mode, room);
}

async function loadRoom(mode, code) {
    const data = await api(`/rooms/${code}?player_token=${encodeURIComponent(state.playerToken)}`);
    if (mode === 'online') {
        state.onlineRoom = data.room;
        state.onlineRoomCode = data.room.code;
        localStorage.setItem(onlineRoomKey, data.room.code);
    } else {
        state.vsRoom = data.room;
        state.vsRoomCode = data.room.code;
        localStorage.setItem(vsRoomKey, data.room.code);
    }

    await renderRoom(mode, data.room);
    await loadAchievements();
    await loadGacha();
}

async function createRoom(mode, nickname, difficulty, extra = {}) {
    const response = await api('/rooms/create', {
        method: 'POST',
        data: {
            mode,
            difficulty,
            nickname,
            language: state.language,
            visibility: extra.visibility || 'private',
            room_name: extra.roomName || null,
            player_token: state.playerToken || null,
        },
    });

    setPlayerToken(response.player_token);
    await loadProfile();
    await loadRoom(mode, response.room.code);
    await loadAllPublicRooms();
}

async function joinRoom(mode, code, nickname) {
    const response = await api('/rooms/join', {
        method: 'POST',
        data: {
            code: code.toUpperCase(),
            nickname,
            player_token: state.playerToken || null,
        },
    });

    setPlayerToken(response.player_token);
    await loadProfile();
    await loadRoom(mode, response.room.code);
}

async function loadPublicRooms(mode) {
    const listElement = mode === 'online' ? onlinePublicRoomsList : vsPublicRoomsList;
    const payload = await api(`/rooms/public?mode=${mode}&lang=${state.language}`);
    const rooms = payload.rooms || [];

    if (!rooms.length) {
        listElement.innerHTML = `<p class="muted">${t('noPublicRooms')}</p>`;
        return;
    }

    listElement.innerHTML = rooms.map((room) => `
        <button type="button" class="search-item" data-action="join-public" data-mode="${mode}" data-code="${room.code}" ${room.is_joinable ? '' : 'disabled'}>
            <img src="${pokeballSpriteUrl()}" alt="room">
            <span>${room.room_name || 'Sala sin nombre'} · ${room.code} · ${room.players_count}/2 · ${room.difficulty} · ${room.language.toUpperCase()}</span>
        </button>
    `).join('');

    listElement.querySelectorAll('[data-action="join-public"]').forEach((button) => {
        button.addEventListener('click', async () => {
            const selectedMode = button.dataset.mode;
            const nickname = selectedMode === 'online'
                ? (document.getElementById('online-join-nickname').value || document.getElementById('online-nickname').value)
                : (document.getElementById('vs-join-nickname').value || document.getElementById('vs-nickname').value);

            if (!nickname.trim()) {
                alert(t('joinNeedName'));
                return;
            }

            try {
                await joinRoom(selectedMode, button.dataset.code, nickname);
            } catch (error) {
                alert(error.message);
            }
        });
    });
}

async function loadAllPublicRooms() {
    await Promise.all([loadPublicRooms('online'), loadPublicRooms('vs')]);
}

authRegisterBtn.addEventListener('click', async () => {
    try {
        await registerAuth();
    } catch (error) {
        alert(error.message);
    }
});
authLoginBtn.addEventListener('click', async () => {
    try {
        await loginAuth();
    } catch (error) {
        alert(error.message);
    }
});
authLogoutBtn.addEventListener('click', async () => {
    await logoutAuth();
});
profileLogoutBtn?.addEventListener('click', async () => {
    await logoutAuth();
});

// New: Auth form toggle handlers
authToggleRegisterBtn?.addEventListener('click', () => {
    // Show register fields
    authNameField?.classList.remove('hidden');
    authPasswordConfirmField?.classList.remove('hidden');
    // Hide login button, show register button
    authLoginBtn?.classList.add('hidden');
    authRegisterBtn?.classList.remove('hidden');
    // Toggle toggle text
    authLoginToggleText?.classList.add('hidden');
    authRegisterToggleText?.classList.remove('hidden');
});

authToggleLoginBtn?.addEventListener('click', () => {
    // Hide register fields
    authNameField?.classList.add('hidden');
    authPasswordConfirmField?.classList.add('hidden');
    // Show login button, hide register button
    authLoginBtn?.classList.remove('hidden');
    authRegisterBtn?.classList.add('hidden');
    // Toggle toggle text
    authLoginToggleText?.classList.remove('hidden');
    authRegisterToggleText?.classList.add('hidden');
});

// New: Profile edit handlers
profileEditBtn?.addEventListener('click', () => {
    profileCard?.classList.add('hidden');
    profileEditSection?.classList.remove('hidden');
});
profileCancelBtn?.addEventListener('click', () => {
    profileEditSection?.classList.add('hidden');
    profileCard?.classList.remove('hidden');
});
// Gacha close button - with fallback
if (gachaCinematicClose) {
    gachaCinematicClose.addEventListener('click', closeGachaCinematic);
} else {
    console.warn('gacha-cinematic-close button not found');
}

// Gacha backdrop click to close - with fallback
if (gachaCinematic) {
    gachaCinematic.addEventListener('click', (event) => {
        if (event.target === gachaCinematic) {
            closeGachaCinematic();
        }
    });
    
    // Keyboard escape to close - with safeguard
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && gachaCinematic && !gachaCinematic.classList.contains('hidden')) {
            closeGachaCinematic();
        }
    });
} else {
    console.warn('gacha-cinematic element not found');
}
gachaOpenBtn.addEventListener('click', async () => {
    await openGacha();
});
profileSaveBtn.addEventListener('click', async () => {
    try {
        await saveProfile();
    } catch (error) {
        alert(error.message);
    }
});
localSearchBtn.addEventListener('click', handleLocalSearch);
localSearchInput.addEventListener('input', debounce(handleLocalSearch, 250));
localEvaluateBtn.addEventListener('click', evaluateLocalQuestion);
refreshOnlinePublicBtn.addEventListener('click', () => loadPublicRooms('online'));
refreshVsPublicBtn.addEventListener('click', () => loadPublicRooms('vs'));

languageSelect.addEventListener('change', async () => {
    setLanguage(languageSelect.value);
    await loadQuestionCatalog();
    await loadAllPublicRooms();

    if (state.onlineRoomCode) {
        await loadRoom('online', state.onlineRoomCode);
    }

    if (state.vsRoomCode) {
        await loadRoom('vs', state.vsRoomCode);
    }
});

const onlineCreateBtn = document.getElementById('online-create-btn');
const onlineJoinBtn = document.getElementById('online-join-btn');
const vsCreateBtn = document.getElementById('vs-create-btn');
const vsJoinBtn = document.getElementById('vs-join-btn');

onlineCreateBtn.addEventListener('click', async () => {
    try {
        await createRoom(
            'online',
            document.getElementById('online-nickname').value,
            document.getElementById('online-difficulty').value,
            {
                visibility: document.getElementById('online-visibility').value,
                roomName: document.getElementById('online-room-name').value,
            },
        );
    } catch (error) {
        alert(error.message);
    }
});

onlineJoinBtn.addEventListener('click', async () => {
    try {
        await joinRoom(
            'online',
            document.getElementById('online-code').value,
            document.getElementById('online-join-nickname').value,
        );
    } catch (error) {
        alert(error.message);
    }
});

vsCreateBtn.addEventListener('click', async () => {
    try {
        await createRoom(
            'vs',
            document.getElementById('vs-nickname').value,
            document.getElementById('vs-difficulty').value,
            {
                visibility: document.getElementById('vs-visibility').value,
                roomName: document.getElementById('vs-room-name').value,
            },
        );
    } catch (error) {
        alert(error.message);
    }
});

vsJoinBtn.addEventListener('click', async () => {
    try {
        await joinRoom(
            'vs',
            document.getElementById('vs-code').value,
            document.getElementById('vs-join-nickname').value,
        );
    } catch (error) {
        alert(error.message);
    }
});

setInterval(async () => {
    try {
        if (isUserTyping()) {
            return;
        }

        if (state.onlineRoomCode) {
            await loadRoom('online', state.onlineRoomCode);
        }

        if (state.vsRoomCode) {
            await loadRoom('vs', state.vsRoomCode);
        }
    } catch (error) {
        // polling silencioso
    }
}, 3000);

// Client-side timer countdown (visual only, ticks between polls)
setInterval(() => {
    document.querySelectorAll('.room-timer').forEach((timerEl) => {
        timerEl.querySelectorAll('.timer-clock').forEach((clock) => {
            const match = clock.textContent.match(/(\d+):(\d+)/);
            if (!match) return;
            let total = parseInt(match[1]) * 60 + parseInt(match[2]);
            if (total > 0) total--;
            const m = Math.floor(total / 60);
            const s = total % 60;
            const label = clock.textContent.startsWith('⏱ Tú') ? '⏱ Tú: ' : '⏱ Rival: ';
            clock.textContent = `${label}${m}:${s.toString().padStart(2, '0')}`;
            if (total <= 30) {
                clock.classList.add('timer-danger');
            }
        });
    });
}, 1000);

setInterval(async () => {
    try {
        if (isUserTyping()) {
            return;
        }

        await loadAllPublicRooms();
    } catch (error) {
        // polling silencioso
    }
}, 15000);

(async function init() {
    setLanguage(state.language);
    updateGoogleAuthLink();
    consumeAuthFromUrl();
    
    // Force gacha modal hidden immediately
    if (gachaCinematic) {
        gachaCinematic.classList.add('hidden');
        gachaCinematic.style.display = 'none !important';
    }

    try {
        await loadAuthMe();
        await loadQuestionCatalog();
        await loadProfile();
        await loadGacha();
        await loadAllPublicRooms();

        // Restore active rooms from localStorage
        if (state.onlineRoomCode) {
            try {
                await loadRoom('online', state.onlineRoomCode);
                tabButtons.forEach((b) => b.classList.toggle('active', b.dataset.mode === 'online'));
                modePanels.forEach((p) => p.classList.toggle('active', p.id === 'mode-online'));
            } catch (_) {
                state.onlineRoomCode = '';
                localStorage.removeItem(onlineRoomKey);
            }
        }
        if (state.vsRoomCode) {
            try {
                await loadRoom('vs', state.vsRoomCode);
                tabButtons.forEach((b) => b.classList.toggle('active', b.dataset.mode === 'vs'));
                modePanels.forEach((p) => p.classList.toggle('active', p.id === 'mode-vs'));
            } catch (_) {
                state.vsRoomCode = '';
                localStorage.removeItem(vsRoomKey);
            }
        }

        const pokemon = await api('/pokemon?limit=1');
        syncStatus.textContent = `${t('loaded')}: ${pokemon.total_loaded}`;
        renderProfileHud();
        populateTypeFilter();
        loadPokedexPage();
        
        // Double-check: force modal hidden again after all loading
        if (gachaCinematic) {
            gachaCinematic.classList.add('hidden');
            gachaCinematic.style.display = 'none';
        }
    } catch (error) {
        syncStatus.textContent = t('syncError');
        // Even on error, keep modal hidden
        if (gachaCinematic) {
            gachaCinematic.classList.add('hidden');
        }
    }
    
    // Final safeguard: ensure modal is hidden after everything, async
    setTimeout(() => {
        if (gachaCinematic && !gachaCinematic.classList.contains('hidden')) {
            gachaCinematic.classList.add('hidden');
            gachaCinematic.style.display = 'none';
        }
    }, 100);
})();
