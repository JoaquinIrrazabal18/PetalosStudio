document.addEventListener('DOMContentLoaded', () => {
    // --- ESTADO Y CONFIGURACIÓN DEL JUEGO ---
    const gameState = {
        partidaId: new URLSearchParams(window.location.search).get('id'),
        miId: window.PHP_VARS ? PHP_VARS.idUsuario : 1,
        jugadorActivo: null,
        turnoActual: 1,
        rondaActual: 1,
        estado: 'cargando',
        restriccionDado: null,
        dinoSeleccionado: null,
        isPaused: false,
        playerHands: {},
        playerBoards: {},
        jugadoresQueHanColocado: new Set(),
        boardType: 'verano',
        players: []
    };

    const ALL_DINOS = {
        'T-Rex': { src: 'public/img/t-rex.png' },
        'Triceratops': { src: 'public/img/triceratops.png' },
        'Stegosaurus': { src: 'public/img/stegosaurus.png' },
        'Pterodactilo': { src: 'public/img/pterodactilo.png' },
        'Plesiosaurio': { src: 'public/img/plesiosaurio.png' },
        'Brachiosaurus': { src: 'public/img/brachiosaurus.png' }
    };

    const playerProfiles = [
        { id: 1, name: 'Tú', pfp: 'https://placehold.co/100x100/0d6efd/ffffff?text=Tú', games: 128, wins: 45, desc: 'Estratega y amante de los T-Rex.' },
        { id: 2, name: 'Xavier', pfp: 'https://placehold.co/100x100/198754/ffffff?text=X', games: 99, wins: 30, desc: 'Siempre apostando por la diversidad de especies.' },
        { id: 3, name: 'Julieta', pfp: 'https://placehold.co/100x100/ffc107/ffffff?text=J', games: 210, wins: 80, desc: 'Coleccionista de parejas de dinosaurios.' },
        { id: 4, name: 'Joaquín', pfp: 'https://placehold.co/100x100/dc3545/ffffff?text=J', games: 50, wins: 15, desc: 'Nuevo en el parque, ¡pero con ganas de aprender!' }
    ];

    const diceRestrictionsMap = {
        1: { id: 'bosque', texto: 'en un recinto del Bosque' },
        2: { id: 'banos', texto: 'a la derecha del río (Baños)' },
        3: { id: 'vacio', texto: 'en un recinto vacío' },
        4: { id: 'sin_trex', texto: 'en un recinto que no contenga un T-Rex' },
        5: { id: 'llanura', texto: 'en un recinto de la Llanura' },
        6: { id: 'cafeteria', texto: 'a la izquierda del río (Cafetería)' }
    };

    const rollButton = document.getElementById('roll-dice-btn');
    const turnMessage = document.getElementById('turn-message');
    const historyLog = document.getElementById('history-log');
    const dinoHandDesktop = document.getElementById('dino-hand-desktop');
    const dinoHandMobile = document.getElementById('dino-hand-mobile');
    const mainBoard = document.getElementById('mi-tablero');
    const dinosColocadosContador = document.getElementById('dinos-colocados-contador');
    const rondaActualContador = document.getElementById('ronda-actual-contador');
    const turnoActualContador = document.getElementById('turno-actual-contador');
    const changeBoardBtn = document.getElementById('change-board-btn');
    const infoRondaCard = document.getElementById('info-ronda-card');
    const oponentesContainer = document.getElementById('oponentes-container');
    const playerCardsContainer = document.getElementById('player-cards-container');

    // --- LÓGICA DE JUEGO (CLIENT-SIDE) ---
    function validarColocacion(dinoEspecie, recintoNombre, dropZone, playerId) {
        const boardState = gameState.playerBoards[playerId] || {};
        const dinosEnRecinto = boardState[recintoNombre] || [];
        const pos = parseInt(dropZone.dataset.posicion);

        if (gameState.jugadorActivo !== playerId && gameState.restriccionDado) {
            const recintoEl = dropZone.closest('.recinto');
            let cumpleDado = false;
            switch (gameState.restriccionDado.id) {
                case 'bosque': cumpleDado = recintoEl.dataset.tipo === 'bosque'; break;
                case 'llanura': cumpleDado = recintoEl.dataset.tipo === 'llanura'; break;
                case 'cafeteria': cumpleDado = recintoEl.dataset.lado === 'cafeteria'; break;
                case 'banos': cumpleDado = recintoEl.dataset.lado === 'banos'; break;
                case 'vacio': cumpleDado = dinosEnRecinto.length === 0; break;
                case 'sin_trex': cumpleDado = !dinosEnRecinto.some(d => d.especie === 'T-Rex'); break;
            }
            if (recintoEl.dataset.tipo === 'rio') cumpleDado = true;
            if (!cumpleDado) {
                if(playerId === gameState.miId) showToast(`Regla del dado: Debes colocar ${gameState.restriccionDado.texto}. El Río siempre es una opción.`);
                return false;
            }
        }

        switch (recintoNombre) {
            case 'Bosque de la Semejanza':
            case 'Prado de la Diferencia':
            case 'Bosque Ordenado':
                if (pos > 0 && !dinosEnRecinto.some(d => d.posicion === (pos - 1))) {
                   if(playerId === gameState.miId) showToast('Debes llenar este recinto de izquierda a derecha sin dejar huecos.');
                    return false;
                }
                if (recintoNombre === 'Bosque de la Semejanza' && dinosEnRecinto.length > 0 && dinosEnRecinto[0].especie !== dinoEspecie) {
                   if(playerId === gameState.miId) showToast('Este bosque solo puede tener dinosaurios de la misma especie.');
                    return false;
                }
                if (recintoNombre === 'Prado de la Diferencia' && dinosEnRecinto.some(d => d.especie === dinoEspecie)) {
                   if(playerId === gameState.miId) showToast('Este prado solo puede tener dinosaurios de especies diferentes.');
                    return false;
                }
                if (recintoNombre === 'Bosque Ordenado' && dinosEnRecinto.length > 0) {
                    const primeraEspecie = dinosEnRecinto[0].especie;
                    const segundaEspecie = dinosEnRecinto.find(d => d.especie !== primeraEspecie)?.especie;
                    if (segundaEspecie) {
                        const esPosicionPar = dinosEnRecinto.length % 2 === 0;
                        if (esPosicionPar && dinoEspecie !== primeraEspecie) return false;
                        if (!esPosicionPar && dinoEspecie !== segundaEspecie) return false;
                    } else {
                        if (dinoEspecie === primeraEspecie) return false;
                    }
                }
                break;
            case 'Trío Frondoso':
                if (dinosEnRecinto.length >= 3) {
                   if(playerId === gameState.miId) showToast('Este recinto no puede tener más de 3 dinosaurios.');
                    return false;
                }
                break;
            case 'Rey de la Selva':
            case 'Isla Solitaria':
            case 'Puesto de Observación':
            case 'Zona de Cuarentena':
                if (dinosEnRecinto.length >= 1) {
                   if(playerId === gameState.miId) showToast('Este recinto solo puede albergar 1 dinosaurio.');
                    return false;
                }
                break;
            case 'La Pirámide':
                const count = dinosEnRecinto.length;
                if ((count < 3 && pos > 2) || (count >= 3 && count < 5 && (pos < 3 || pos > 4)) || (count === 5 && pos !== 5) || count >= 6) {
                    if(playerId === gameState.miId) showToast('Debes llenar la pirámide por niveles: primero la base, luego el medio y al final la cima.');
                    return false;
                }
                const adyacentes = { 0: [1, 3], 1: [0, 2, 3, 4], 2: [1, 4], 3: [0, 1, 5], 4: [1, 2, 5], 5: [3, 4] };
                const dinosVecinos = (adyacentes[pos] || []).map(p => dinosEnRecinto.find(d => d.posicion === p)).filter(Boolean);
                if (dinosVecinos.some(vecino => vecino.especie === dinoEspecie)) {
                   if(playerId === gameState.miId) showToast('No puedes colocar dinosaurios de la misma especie en casillas adyacentes.');
                    return false;
                }
                break;
        }
        return true;
    }

    function handleDinoClick(e) {
        const dinoElement = e.target.closest('.dinosaurio-mano');
        if (!dinoElement) return;
        if (gameState.estado !== 'en_curso' || gameState.jugadorActivo !== gameState.miId || gameState.jugadoresQueHanColocado.has(gameState.miId)) {
            return;
        }
        document.querySelectorAll('.dinosaurio-mano').forEach(d => d.classList.remove('seleccionado'));
        if (gameState.dinoSeleccionado === dinoElement) {
            gameState.dinoSeleccionado = null;
            deshabilitarTodasLasZonas();
        } else {
            dinoElement.classList.add('seleccionado');
            gameState.dinoSeleccionado = dinoElement;
            habilitarZonasParaJugador();
        }
    }

    async function handleZoneClick(e) {
        const dropZone = e.target.closest('.drop-zone');
        if (!dropZone || !dropZone.classList.contains('habilitado') || !gameState.dinoSeleccionado || gameState.jugadoresQueHanColocado.has(gameState.miId)) return;
        
        const dinoEspecie = gameState.dinoSeleccionado.dataset.dinoEspecie;
        const recintoNombre = dropZone.closest('.recinto').dataset.recintoNombre;
        if (!validarColocacion(dinoEspecie, recintoNombre, dropZone, gameState.miId)) {
            return;
        }

        const payload = {
            id_partida: gameState.partidaId,
            id_dino_ficha: gameState.dinoSeleccionado.dataset.dinoId,
            recinto: recintoNombre,
            posicion: dropZone.dataset.posicion,
        };

        const response = await fetch('api/index.php/partidas/movimiento', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (result.success) {
            showToast('¡Movimiento realizado!', 'success');
            fetchGameState(); 
        } else {
            showToast(result.message || 'Movimiento inválido según el servidor.');
        }
    }
    
    // --- LÓGICA DE RED ---
    async function fetchGameState() {
        try {
            const response = await fetch(`api/index.php/partidas/estado?id_partida=${gameState.partidaId}`);
            if (!response.ok) {
                console.error("Error fetching game state:", response.statusText);
                return;
            }
            const result = await response.json();
            if(result.success) {
                updateFrontendState(result.data);
            }
        } catch (error) {
            console.error("Error de conexión:", error);
        }
    }

    function updateFrontendState(newState) {
        if (gameState.players.length === 0 && newState.participantes) {
            gameState.players.push(...newState.participantes.map((p, i) => ({...p, colorClass: `user-color-${i+1}`})));
            renderOpponentCards();
            setupPlayerCards();
        }

        gameState.estado = newState.estado_partida;
        gameState.rondaActual = newState.ronda_actual;
        gameState.turnoActual = newState.turno_actual;
        gameState.jugadorActivo = newState.jugador_activo.id_usuario;
        gameState.jugadoresQueHanColocado = new Set(newState.jugadores_que_han_colocado || []);
        
        if (gameState.boardType !== newState.lado_tablero_usado.toLowerCase()) {
            setupBoard(newState.lado_tablero_usado.toLowerCase());
        }

        gameState.playerHands = newState.manos || {};
        actualizarManoUI(gameState.playerHands[gameState.miId] || []);

        gameState.playerBoards = newState.tableros || {};
        renderAllBoards();
        updateAllPlayerCardCounts();
        updateLiveScores();
        
        updateUI();
    }

    // --- RENDERIZADO Y UI ---
    function renderOpponentCards() {
        oponentesContainer.innerHTML = '';
        const opponents = gameState.players.filter(p => p.id_usuario !== gameState.miId);
        opponents.forEach((opponent) => {
            const opponentHTML = `
                <div class="col-lg-4 col-md-6">
                    <div class="card shadow-sm opponent-board-wrapper">
                        <div class="card-header small fw-semibold ${opponent.colorClass} text-start border-0">${opponent.nombre_usuario}</div>
                        <div class="opponent-board" data-oponente-id="${opponent.id_usuario}">
                            <img src="public/img/tablero verano.jpg" class="card-img-top">
                        </div>
                    </div>
                </div>`;
            oponentesContainer.innerHTML += opponentHTML;
        });
    }

    function renderAllBoards() {
        document.querySelectorAll('.dino-en-tablero, .oponente-dino').forEach(el => el.remove());

        for (const playerId in gameState.playerBoards) {
            const boardData = gameState.playerBoards[playerId];
            for (const recinto in boardData) {
                boardData[recinto].forEach(dino => {
                    const dinoData = ALL_DINOS[dino.especie];
                    if (!dinoData) return;

                    const zone = document.querySelector(`.recinto[data-recinto-nombre="${recinto}"] .drop-zone[data-posicion="${dino.posicion}"]`);
                    if (!zone) continue;

                    if (parseInt(playerId) === gameState.miId) {
                        if(zone.innerHTML === '') {
                            const dinoImg = document.createElement('img');
                            dinoImg.src = dinoData.src;
                            dinoImg.className = 'dino-en-tablero';
                            zone.appendChild(dinoImg);
                        }
                    } else {
                        const opponentBoardEl = document.querySelector(`.opponent-board[data-oponente-id="${playerId}"]`);
                        if (opponentBoardEl) {
                             const dinoImg = document.createElement('img');
                             dinoImg.src = dinoData.src;
                             dinoImg.classList.add('oponente-dino');
                             const rect = zone.getBoundingClientRect();
                             const boardRect = mainBoard.getBoundingClientRect();
                             if(boardRect.height > 0 && boardRect.width > 0) {
                                dinoImg.style.top = `${((rect.top - boardRect.top) / boardRect.height) * 100}%`;
                                dinoImg.style.left = `${((rect.left - boardRect.left) / boardRect.width) * 100}%`;
                                opponentBoardEl.appendChild(dinoImg);
                             }
                        }
                    }
                });
            }
        }
    }

    function setupPlayerCards() {
        playerCardsContainer.innerHTML = '';
        gameState.players.forEach((player) => {
            const cardHTML = `
                <div class="col-6">
                    <div class="card h-100 player-card-clickable" id="player-card-${player.id_usuario}" data-bs-toggle="modal" data-bs-target="#profileModal" data-player-id="${player.id_usuario}">
                        <div class="card-body text-center d-flex flex-column">
                            <p class="fw-semibold ${player.colorClass} mb-2">${player.nombre_usuario}</p>
                            <p class="score-icon fw-bold small mb-2"><i class="bi bi-star-fill text-warning"></i> <span class="player-score">0</span></p>
                            <div class="d-flex flex-wrap justify-content-center mt-auto" style="gap: 4px 8px;">
                                <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="T-Rex">0</p><img src="public/img/t-rex.png" class="dino-score-icon"></div>
                                <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Triceratops">0</p><img src="public/img/triceratops.png" class="dino-score-icon"></div>
                                <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Stegosaurus">0</p><img src="public/img/stegosaurus.png" class="dino-score-icon"></div>
                                <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Pterodactilo">0</p><img src="public/img/pterodactilo.png" class="dino-score-icon"></div>
                                <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Plesiosaurio">0</p><img src="public/img/plesiosaurio.png" class="dino-score-icon"></div>
                                <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Brachiosaurus">0</p><img src="public/img/brachiosaurus.png" class="dino-score-icon"></div>
                            </div>
                        </div>
                    </div>
                </div>`;
            playerCardsContainer.innerHTML += cardHTML;
        });
    }
    
    function actualizarManoUI(mano) {
        dinoHandDesktop.innerHTML = '<h6 class="fw-bold small text-center my-1">Dinos en Mano</h6>';
        dinoHandMobile.innerHTML = '';
        mano.forEach(dino => {
            const img = document.createElement('img');
            img.src = ALL_DINOS[dino.especie].src;
            img.alt = dino.especie;
            img.className = 'dinosaurio-mano';
            img.dataset.dinoId = dino.id_dino_ficha;
            img.dataset.dinoEspecie = dino.especie;
            dinoHandDesktop.appendChild(img);
            dinoHandMobile.appendChild(img.cloneNode(true));
        });
    }

    function updateUI() {
        const activePlayer = gameState.players.find(p => p.id_usuario === gameState.jugadorActivo);
        if(!activePlayer) return;

        const esMiTurnoDeLanzar = activePlayer.id_usuario === gameState.miId;
        rollButton.disabled = !esMiTurnoDeLanzar || gameState.estado !== 'en_curso' || gameState.jugadoresQueHanColocado.size > 0;

        if (gameState.estado === 'en_curso') {
            if(gameState.jugadoresQueHanColocado.size > 0) {
                 turnMessage.innerHTML = gameState.jugadoresQueHanColocado.has(gameState.miId) ? 'Esperando a los demás jugadores...' : '<strong>Elige un dino y colócalo en tu parque.</strong>';
            } else {
                 turnMessage.innerHTML = esMiTurnoDeLanzar ? `<strong>¡Te toca lanzar!</strong>` : `Turno de <strong class="${activePlayer.colorClass}">${activePlayer.nombre_usuario}</strong> para lanzar.`;
            }
        }
        
        let totalDinosColocados = 0;
        if(gameState.playerBoards[gameState.miId]) {
            for(const recinto in gameState.playerBoards[gameState.miId]){
                totalDinosColocados += gameState.playerBoards[gameState.miId][recinto].length;
            }
        }
        
        dinosColocadosContador.textContent = `${totalDinosColocados} / 12`;
        rondaActualContador.textContent = `${gameState.rondaActual} / 2`;
        turnoActualContador.textContent = `${gameState.turnoActual} / 6`;
        
        document.querySelectorAll('.dice-turn-icon').forEach(icon => icon.remove());
        const playerCard = document.getElementById(`player-card-${activePlayer.id_usuario}`);
        if (playerCard) {
            playerCard.querySelector('.card-body').insertAdjacentHTML('beforeend', `<i class="bi bi-dice-6-fill dice-turn-icon" title="Lanza el dado"></i>`);
        }
    }
    
    function addHistory(message) {
        historyLog.innerHTML += `<p class="mb-1">${message}</p>`;
        historyLog.scrollTop = historyLog.scrollHeight;
    }

    function showToast(message, type = 'danger') {
        const toastContainer = document.getElementById('toast-container') || document.createElement('div');
        if (!toastContainer.id) {
            toastContainer.id = 'toast-container';
            toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = 1055;
            document.body.appendChild(toastContainer);
        }
        const toastEl = document.createElement('div');
        toastEl.className = `toast show align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }

    function setupBoard(type) {
        gameState.boardType = type;
        const body = document.body;
        const boardImg = mainBoard.querySelector('.board-image');
        const veranoZones = document.getElementById('verano-zones');
        const inviernoZones = document.getElementById('invierno-zones');

        body.classList.remove('theme-verano', 'theme-invierno');

        if (type === 'invierno') {
            body.classList.add('theme-invierno');
            boardImg.src = 'public/img/tablero invierno.jpg';
            veranoZones.style.display = 'none';
            inviernoZones.style.display = 'block';
            document.getElementById("gameNameDisplay").textContent = "Invierno Glacial";
            infoRondaCard.classList.remove('bg-success');
            infoRondaCard.classList.add('bg-primary');
        } else {
            body.classList.add('theme-verano');
            boardImg.src = 'public/img/tablero verano.jpg';
            veranoZones.style.display = 'block';
            inviernoZones.style.display = 'none';
            document.getElementById("gameNameDisplay").textContent = "Verano Jurásico";
            infoRondaCard.classList.add('bg-success');
            infoRondaCard.classList.remove('bg-primary');
        }

        document.querySelectorAll('.opponent-board img').forEach(img => img.src = boardImg.src);
    }

    function init() {
        [dinoHandDesktop, dinoHandMobile, mainBoard].forEach(el => el.addEventListener('click', e => {
            if (e.target.closest('.dinosaurio-mano')) handleDinoClick(e);
            else if (e.target.closest('.drop-zone')) handleZoneClick(e);
        }));
        rollButton.addEventListener('click', () => rollDiceAnimation());
        
        changeBoardBtn.addEventListener('click', () => {
            if(gameState.rondaActual > 1 || gameState.turnoActual > 1) {
                showToast("No se puede cambiar el tablero una vez iniciada la partida.");
                return;
            }
            const newType = gameState.boardType === 'verano' ? 'invierno' : 'verano';
            setupBoard(newType);
        });

        setupLinkBar();
        setupModals();
        fetchGameState();
        setInterval(fetchGameState, 3000);
        animate();
    }

    function setupLinkBar() {
        const copyContainer = document.getElementById('copy-container');
        const copyFeedback = document.getElementById('copy-feedback');
        const linkText = document.getElementById('link-text');
        const togglePasswordButton = document.getElementById('toggle-password');
        const passwordText = document.getElementById('password-text');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');
        let isInfoVisible = false;

        copyContainer.addEventListener('click', () => {
            navigator.clipboard.writeText(linkText.dataset.link).then(() => {
                copyFeedback.classList.remove('d-none');
                setTimeout(() => { copyFeedback.classList.add('d-none'); }, 2000);
            });
        });

        togglePasswordButton.addEventListener('click', () => {
            isInfoVisible = !isInfoVisible;
            passwordText.textContent = isInfoVisible ? passwordText.dataset.password : '*'.repeat(passwordText.dataset.password.length);
            linkText.textContent = isInfoVisible ? linkText.dataset.link : '*'.repeat(linkText.dataset.link.length);
            eyeOpen.classList.toggle('d-none', !isInfoVisible);
            eyeClosed.classList.toggle('d-none', isInfoVisible);
        });
    }

    function setupModals() {
        const profileModal = document.getElementById('profileModal');
        profileModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const playerId = parseInt(button.getAttribute('data-player-id'));
            const profileData = playerProfiles.find(p => p.id === playerId) || gameState.players.find(p => p.id_usuario === playerId);
            if (profileData) {
                profileModal.querySelector('.modal-title').textContent = `Perfil de ${profileData.name || profileData.nombre_usuario}`;
                profileModal.querySelector('#profile-pic').src = profileData.pfp || `https://placehold.co/100x100?text=${(profileData.name || profileData.nombre_usuario).charAt(0)}`;
                profileModal.querySelector('#profile-name').textContent = profileData.name || profileData.nombre_usuario;
                profileModal.querySelector('#profile-games').textContent = profileData.games || 'N/A';
                profileModal.querySelector('#profile-wins').textContent = profileData.wins || 'N/A';
                profileModal.querySelector('#profile-desc').textContent = profileData.desc || 'Un nuevo jugador en el parque.';
            }
        });
    }

    const container = document.getElementById('dice-container');
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    container.appendChild(renderer.domElement);
    scene.add(new THREE.AmbientLight(0xffffff, 0.8));
    const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
    directionalLight.position.set(5, 10, 7.5);
    scene.add(directionalLight);

    const loader = new THREE.TextureLoader();
    const materials = [
        new THREE.MeshLambertMaterial({ map: loader.load('public/img/bosque.png') }), 
        new THREE.MeshLambertMaterial({ map: loader.load('public/img/banos.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('public/img/vacio.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('public/img/t-rexs.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('public/img/rocas.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('public/img/cafeteria.png') })
    ];

    const dice = new THREE.Mesh(new THREE.BoxGeometry(), materials);
    scene.add(dice);
    camera.position.z = 1.8;

    function animate(time) {
        requestAnimationFrame(animate);
        if (typeof TWEEN !== 'undefined') TWEEN.update(time);
        renderer.render(scene, camera);
    }

    function rollDiceAnimation() {
         if (gameState.estado !== 'en_curso' || gameState.jugadorActivo !== gameState.miId) return;
        rollButton.disabled = true;
        
        // El resultado real se obtendrá del servidor después de la animación.
        const tempResult = Math.floor(Math.random() * 6) + 1;
        const targetRotations = {
            1: { x: 0, y: Math.PI / 2, z: 0 }, 2: { x: 0, y: -Math.PI / 2, z: 0 }, 3: { x: Math.PI / 2, y: 0, z: 0 },
            4: { x: -Math.PI / 2, y: 0, z: 0 }, 5: { x: 0, y: 0, z: 0 }, 6: { x: 0, y: Math.PI, z: 0 }
        };
        const finalRotation = {
            x: targetRotations[tempResult].x + (Math.PI * 4), y: targetRotations[tempResult].y + (Math.PI * 4), z: targetRotations[tempResult].z + (Math.PI * 4)
        };

        new TWEEN.Tween(dice.rotation)
            .to(finalRotation, 1500).easing(TWEEN.Easing.Cubic.Out)
            .onComplete(() => {
                // Aquí se haría la llamada a la API para lanzar el dado
                // Y el fetchGameState() se encargaría de actualizar todo
                showToast("Lanzando dado... (Lógica de API pendiente)");
                // Simulación para continuar el flujo
                 gameState.estado = 'colocando_dino';
                 habilitarZonasParaJugador();
                 updateUI();

            }).start();
    }
    
    
    function startGameWithCountdown() {
        const countdownNumber = document.getElementById('countdown-number');
        const overlay = document.getElementById('pre-game-overlay');
        let count = 3;
        
        countdownNumber.textContent = count;
        
        const interval = setInterval(() => {
            count--;
            if (count > 0) {
                countdownNumber.textContent = count;
            } else if (count === 0) {
                countdownNumber.textContent = '¡JUEGA!';
            } else {
                clearInterval(interval);
                overlay.classList.add('fade-out');
                setTimeout(() => {
                    overlay.style.display = 'none';
                }, 500);
                init();
            }
        }, 1000);
    }
    
    function endGame() {
        gameState.estado = 'fin';
        updateUI();

        const myBoard = gameState.playerBoards[gameState.miId];
        if (myBoard['Zona de Cuarentena'] && myBoard['Zona de Cuarentena'].length > 0) {
            handleQuarantineChoice(); 
        } else {
            finalizeScoreCalculation();
        }
    }
    
    function finalizeScoreCalculation() {
        addHistory(`<strong>¡FIN DE LA PARTIDA!</strong>`);
        turnMessage.innerHTML = '<strong>¡Partida Finalizada!</strong>';
        rollButton.disabled = true;
        const finalScores = calculateFinalScores();

        finalScores.forEach(playerScore => {
            const board = gameState.playerBoards[playerScore.id];
            playerScore.trexCount = Object.values(board).flat().filter(d => d.especie === 'T-Rex').length;
        });

        const scoresList = document.getElementById('final-scores-list');
        scoresList.innerHTML = '';
        
        finalScores.sort((a, b) => {
            if (b.score !== a.score) {
                return b.score - a.score;
            }
            return a.trexCount - b.trexCount; 
        });

        let lastScore = -1, lastTrexCount = -1, rank = 0;
        finalScores.forEach((playerScore, index) => {
            if (playerScore.score !== lastScore || playerScore.trexCount !== lastTrexCount) {
                rank = index + 1;
                lastScore = playerScore.score;
                lastTrexCount = playerScore.trexCount;
            }
            const medal = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : '';
            scoresList.innerHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold fs-5 ${playerScore.colorClass}">${medal} ${playerScore.name}</span>
                        <small class="text-muted d-block">T-Rex: ${playerScore.trexCount}</small>
                    </div>
                    <span class="badge bg-primary rounded-pill fs-6">${playerScore.score} Puntos</span>
                </li>`;
        });
        
        const endGameModal = new bootstrap.Modal(document.getElementById('endGameModal'));
        endGameModal.show();
    }

    function handleQuarantineChoice() {
        const quarantineModalEl = document.getElementById('quarantineModal');
        const quarantineModal = new bootstrap.Modal(quarantineModalEl);
        const optionsList = document.getElementById('quarantine-options-list');
        optionsList.innerHTML = '';

        const myBoard = gameState.playerBoards[gameState.miId];
        const dino = myBoard['Zona de Cuarentena'][0];

        const allZones = document.querySelectorAll(`#${gameState.boardType}-zones .drop-zone`);
        allZones.forEach(zone => {
            const recinto = zone.closest('.recinto');
            if(zone.children.length === 0) {
                const option = document.createElement('a');
                option.href = '#';
                option.className = 'list-group-item list-group-item-action';
                option.textContent = recinto.dataset.recintoNombre;
                option.dataset.recintoNombre = recinto.dataset.recintoNombre;
                option.dataset.posicion = zone.dataset.posicion;
                optionsList.appendChild(option);
            }
        });

        const quarantineClickHandler = (e) => {
            e.preventDefault();
            const target = e.target;
            const targetRecinto = target.dataset.recintoNombre;
            const targetPos = parseInt(target.dataset.posicion);
            
            delete myBoard['Zona de Cuarentena'];
            if (!myBoard[targetRecinto]) myBoard[targetRecinto] = [];
            myBoard[targetRecinto].push({ especie: dino.especie, posicion: targetPos });

            document.querySelector('#zona_cuarentena').innerHTML = '';
            const targetZone = document.querySelector(`.recinto[data-recinto-nombre="${targetRecinto}"] .drop-zone[data-posicion="${targetPos}"]`);
             if(targetZone) {
                const dinoImg = document.createElement('img');
                dinoImg.src = ALL_DINOS[dino.especie].src;
                dinoImg.className = 'dino-en-tablero';
                targetZone.appendChild(dinoImg);
            }
            
            quarantineModal.hide();
            quarantineModalEl.removeEventListener('click', quarantineClickHandler);
            finalizeScoreCalculation();
        };
        
        quarantineModalEl.addEventListener('click', quarantineClickHandler);
        quarantineModal.show();
    }

    function calculateLiveScore(playerId) {
        const board = gameState.playerBoards[playerId] || {};
        let totalScore = 0;
        for (const recinto in board) {
            const dinos = board[recinto];
            if (dinos.length === 0) continue;
            switch (recinto) {
                case 'Bosque de la Semejanza': totalScore += ({ 1: 1, 2: 4, 3: 8, 4: 12, 5: 18, 6: 24 }[dinos.length] || 0); break;
                case 'Prado de la Diferencia': totalScore += ({ 1: 1, 2: 3, 3: 6, 4: 10, 5: 15, 6: 21 }[dinos.length] || 0); break;
                case 'Pradera del Amor':
                    const counts = dinos.reduce((acc, dino) => { acc[dino.especie] = (acc[dino.especie] || 0) + 1; return acc; }, {});
                    for (const especie in counts) { totalScore += Math.floor(counts[especie] / 2) * 5; }
                    break;
                case 'Trío Frondoso': if (dinos.length === 3) totalScore += 7; break;
                case 'Isla Solitaria':
                    if(dinos.length > 0) {
                        const especieUnica = dinos[0].especie;
                        if (Object.values(board).flat().filter(d => d.especie === especieUnica).length === 1) totalScore += 7;
                    }
                    break;
                case 'Bosque Ordenado': totalScore += ({ 1: 2, 2: 4, 3: 8, 4: 12, 5: 18, 6: 24 }[dinos.length] || 0); break;
                case 'Puente de los Enamorados Izquierda': break;
                case 'Puente de los Enamorados Derecha':
                    const izq = board['Puente de los Enamorados Izquierda'] || [], der = board['Puente de los Enamorados Derecha'] || [];
                    const countsIzq = izq.reduce((acc, dino) => { acc[dino.especie] = (acc[dino.especie] || 0) + 1; return acc; }, {});
                    const countsDer = der.reduce((acc, dino) => { acc[dino.especie] = (acc[dino.especie] || 0) + 1; return acc; }, {});
                    for(const especie in countsIzq) { if(countsDer[especie]) { totalScore += Math.min(countsIzq[especie], countsDer[especie]) * 6; } }
                    break;
                case 'La Pirámide':
                    dinos.forEach(dino => {
                        if (dino.posicion <= 2) totalScore += 1;
                        else if (dino.posicion <= 4) totalScore += 2;
                        else if (dino.posicion === 5) totalScore += 3;
                    });
                    break;
                case 'Rio': totalScore += dinos.length; break;
            }
        }
        const recintosConTRex = new Set();
        for (const recinto in board) {
            if (board[recinto].some(d => d.especie === 'T-Rex') && recinto !== 'Rio') {
                recintosConTRex.add(recinto);
            }
        }
        totalScore += recintosConTRex.size;
        return totalScore;
    }

    function calculateFinalScores() {
        const allPlayerScores = [];
        gameState.players.forEach(player => {
            let totalScore = calculateLiveScore(player.id_usuario);
            const board = gameState.playerBoards[player.id_usuario];
            if(board['Rey de la Selva'] && board['Rey de la Selva'].length > 0) {
                const miEspecie = board['Rey de la Selva'][0].especie;
                let tengoMas = true;
                const miConteo = Object.values(board).flat().filter(d => d.especie === miEspecie).length;
                for (const otherPlayer of gameState.players) {
                    if (otherPlayer.id_usuario === player.id_usuario) continue;
                    const otroConteo = Object.values(gameState.playerBoards[otherPlayer.id_usuario] || {}).flat().filter(d => d.especie === miEspecie).length;
                    if (otroConteo > miConteo) { tengoMas = false; break; }
                }
                if (tengoMas) totalScore += 7;
            }
            if(board['Puesto de Observación'] && board['Puesto de Observación'].length > 0) {
                const especieObservada = board['Puesto de Observación'][0].especie;
                const playerIndex = gameState.players.findIndex(p => p.id_usuario === player.id_usuario);
                const rightPlayerId = gameState.players[(playerIndex + 1) % gameState.players.length].id_usuario;
                const conteoDerecha = Object.values(gameState.playerBoards[rightPlayerId] || {}).flat().filter(d => d.especie === especieObservada).length;
                totalScore += conteoDerecha * 2;
            }

            allPlayerScores.push({ id: player.id_usuario, name: player.nombre_usuario, colorClass: player.colorClass, score: totalScore });
        });
        return allPlayerScores;
    }

    function updateLiveScores() {
        gameState.players.forEach(player => {
            const score = calculateLiveScore(player.id_usuario);
            const card = document.getElementById(`player-card-${player.id_usuario}`);
            if (card) card.querySelector('.player-score').textContent = score;
        });
    }
    
    function updateAllPlayerCardCounts() {
         gameState.players.forEach(p => updatePlayerCardCounts(p.id_usuario));
    }

    function updatePlayerCardCounts(playerId) {
        const card = document.getElementById(`player-card-${playerId}`);
        if (!card) return;
        const board = gameState.playerBoards[playerId] || {};
        const allDinosOnBoard = Object.values(board).flat();
        card.querySelectorAll('.dino-count').forEach(countEl => {
            const dinoType = countEl.dataset.dino;
            const count = allDinosOnBoard.filter(d => d.especie === dinoType).length;
            countEl.textContent = count;
        });
    }

    startGameWithCountdown();
});

