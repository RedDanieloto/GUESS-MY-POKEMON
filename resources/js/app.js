import './bootstrap';

const apiBase = '/api';
const storageKey = 'pwi_player_token';
const langKey = 'pwi_lang';

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
        profileSaved: 'Perfil guardado.',
        noProfileYet: 'Aun no creas perfil. Puedes jugar sin login o guardar uno opcional.',
        level: 'Nivel',
        wins: 'Victorias',
        games: 'Partidas',
        xpNext: 'XP para siguiente nivel',
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
        profileSaved: 'Profile saved.',
        noProfileYet: 'No profile yet. You can play as guest or save an optional profile.',
        level: 'Level',
        wins: 'Wins',
        games: 'Games',
        xpNext: 'XP for next level',
    },
};

const state = {
    playerToken: localStorage.getItem(storageKey) || '',
    language: localStorage.getItem(langKey) || 'es',
    localPokemon: null,
    localQuestions: {},
    typeLabels: {},
    onlineRoomCode: '',
    onlineRoom: null,
    vsRoomCode: '',
    vsRoom: null,
    profile: null,
    avatarCatalog: {},
};

const syncBtn = document.getElementById('sync-pokemon-btn');
const syncStatus = document.getElementById('sync-status');
const languageSelect = document.getElementById('language-select');

const profileNicknameInput = document.getElementById('profile-nickname');
const profileTierSelect = document.getElementById('profile-tier');
const profileAvatarSelect = document.getElementById('profile-avatar');
const profileSaveBtn = document.getElementById('profile-save-btn');
const profileHud = document.getElementById('profile-hud');

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

function debounce(fn, wait = 280) {
    let timer = null;
    return (...args) => {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(() => fn(...args), wait);
    };
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

function renderProfileHud() {
    if (!state.profile) {
        profileHud.textContent = t('noProfileYet');
        return;
    }

    const avatar = state.avatarCatalog[state.profile.avatar_key] || '';
    profileHud.innerHTML = `
        <div class="profile-hud-row">
            <img class="profile-avatar" src="${avatar}" alt="avatar">
            <div>
                <strong>${state.profile.nickname || 'Trainer'}</strong>
                <div class="muted">${t('level')} ${state.profile.level} · ${state.profile.experience_tier}</div>
                <div class="muted">${t('wins')}: ${state.profile.wins} · ${t('games')}: ${state.profile.games_played}</div>
            </div>
        </div>
        <div class="xp-wrap">
            <div class="muted">XP: ${state.profile.xp} · ${t('xpNext')}: ${state.profile.next_level_xp}</div>
            <div class="xp-track"><div class="xp-fill" style="width:${state.profile.level_progress_percent}%"></div></div>
        </div>
    `;
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
    syncStatus.textContent = t('profileSaved');
}

async function loadProfile() {
    if (!state.playerToken) {
        return;
    }

    const payload = await api(`/profile?player_token=${encodeURIComponent(state.playerToken)}`);
    state.profile = payload.profile;
    state.avatarCatalog = payload.avatar_catalog || state.avatarCatalog;
    renderAvatarOptions();

    if (state.profile) {
        profileNicknameInput.value = state.profile.nickname || '';
        profileTierSelect.value = state.profile.experience_tier || 'beginner';
        profileAvatarSelect.value = state.profile.avatar_key || 'trainer-a';
    }

    renderProfileHud();
}

function pokemonCardHtml(pokemon) {
    const sprite = pokemon?.sprites?.official_artwork || pokemon?.sprites?.front_default || '';
    return `
        <img src="${sprite}" alt="${pokemon.display_name}">
        <div>
            <strong>#${pokemon.pokeapi_id} ${pokemon.display_name}</strong>
            <div class="muted">Gen ${pokemon.generation || '?'} | ${formatType(pokemon.primary_type)}${pokemon.secondary_type ? ` / ${formatType(pokemon.secondary_type)}` : ''}</div>
            <div class="muted">Altura: ${(pokemon.height_dm / 10).toFixed(1)} m | Peso: ${(pokemon.weight_hg / 10).toFixed(1)} kg</div>
            <div>
                ${pokemon.is_legendary ? `<span class="pill">${t('legendary')}</span>` : ''}
                ${pokemon.is_mythical ? `<span class="pill">${t('mythical')}</span>` : ''}
            </div>
        </div>
    `;
}

function searchResultButtonHtml(pokemon, action, textPrefix = '') {
    const sprite = pokemon?.sprites?.front_default || pokemon?.sprites?.official_artwork || '';
    return `<button type="button" class="search-item" data-action="${action}" data-pokemon-id="${pokemon.id}">
        <img src="${sprite}" alt="${pokemon.display_name}">
        <span>${textPrefix}#${pokemon.pokeapi_id} ${pokemon.display_name}</span>
    </button>`;
}

async function syncPokemon() {
    syncBtn.disabled = true;
    syncStatus.textContent = t('syncing');

    try {
        const result = await api('/pokemon/sync', {
            method: 'POST',
            data: { limit: 151, offset: 0 },
        });

        const summary = result.summary || {};
        syncStatus.textContent = `Listo: +${summary.created || 0} nuevos, ${summary.updated || 0} actualizados. Total: ${result.total_loaded}`;
    } catch (error) {
        syncStatus.textContent = error.message;
    } finally {
        syncBtn.disabled = false;
        await loadQuestionCatalog();
    }
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
                        <div class="inline-grid" style="grid-template-columns: repeat(3, 1fr)">
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
        ? `<p><strong>Ganador:</strong> ${room.players.find((player) => player.session_id === room.winner_session_id)?.nickname || 'Jugador'}</p>`
        : '';

    return `
        <div>
            <p><strong>Codigo:</strong> ${room.code} | <strong>Modo:</strong> ${room.mode} | <strong>Dificultad:</strong> ${room.difficulty}</p>
            <p><strong>Visibilidad:</strong> ${room.visibility === 'public' ? t('roomTypePublic') : t('roomTypePrivate')} | <strong>Idioma:</strong> ${room.language.toUpperCase()}</p>
            ${room.room_name ? `<p><strong>Sala:</strong> ${room.room_name}</p>` : ''}
            <p><strong>Estado:</strong> ${room.status}${amTurn ? ' | Te toca' : ''}</p>
            ${winnerHtml}
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
    } else {
        state.vsRoom = data.room;
        state.vsRoomCode = data.room.code;
    }

    await renderRoom(mode, data.room);
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
            <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/poke-ball.png" alt="room">
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

syncBtn.addEventListener('click', syncPokemon);
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

setInterval(async () => {
    try {
        await loadAllPublicRooms();
    } catch (error) {
        // polling silencioso
    }
}, 15000);

(async function init() {
    setLanguage(state.language);

    try {
        await loadQuestionCatalog();
        await loadProfile();
        await loadAllPublicRooms();
        const pokemon = await api('/pokemon?limit=1');
        syncStatus.textContent = `${t('loaded')}: ${pokemon.total_loaded}`;
        renderProfileHud();
    } catch (error) {
        syncStatus.textContent = t('syncError');
    }
})();
