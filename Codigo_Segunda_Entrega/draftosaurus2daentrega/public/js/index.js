document.addEventListener('DOMContentLoaded', () => {
    // --- Lógica para la Barra de Enlace y Contraseña ---
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
        if (isInfoVisible) {
            passwordText.textContent = passwordText.dataset.password;
            linkText.textContent = linkText.dataset.link;
            eyeOpen.classList.remove('d-none');
            eyeClosed.classList.add('d-none');
        } else {
            passwordText.textContent = '*'.repeat(passwordText.dataset.password.length);
            linkText.textContent = '*'.repeat(linkText.dataset.link.length);
            eyeOpen.classList.add('d-none');
            eyeClosed.classList.remove('d-none');
        }
    });

    // --- Lógica de Juego y Turnos ---
    const players = [
        { id: 0, name: 'julisoyyo', colorClass: 'user-color-1' },
        { id: 1, name: 'Usuario1', colorClass: 'user-color-2' },
        { id: 2, name: 'Usuario2', colorClass: 'user-color-3' },
        { id: 3, name: 'Usuario3', colorClass: 'user-color-4' }
    ];
    const playerProfiles = [
        { id: 0, name: 'julisoyyo', pfp: 'https://placehold.co/100x100/0d6efd/ffffff?text=J', games: 128, wins: 45, desc: 'Estratega y amante de los T-Rex.' },
        { id: 1, name: 'Usuario1', pfp: 'https://placehold.co/100x100/198754/ffffff?text=U1', games: 99, wins: 30, desc: 'Siempre apostando por la diversidad de especies.' },
        { id: 2, name: 'Usuario2', pfp: 'https://placehold.co/100x100/ffc107/ffffff?text=U2', games: 210, wins: 80, desc: 'Coleccionista de parejas de dinosaurios.' },
        { id: 3, name: 'Usuario3', pfp: 'https://placehold.co/100x100/dc3545/ffffff?text=U3', games: 50, wins: 15, desc: 'Nuevo en el parque, ¡pero con ganas de aprender!' }
    ];
    const myPlayerId = 0;
    const diceRestrictions = ["en un corral vacío de la izquierda", "en un corral vacío de la derecha", "en el bosque", "en la pradera", "donde no haya T-Rex", "donde quieras"];

    let currentPlayerIndex = 1;
    let gameState = 'waiting_for_roll';
    let isPaused = false;
    let gameLoopTimeout;
    const rollButton = document.getElementById('roll-dice-btn');
    const turnMessage = document.getElementById('turn-message');
    const historyLog = document.getElementById('history-log');

    function addHistory(message) {
        historyLog.innerHTML += `<p class="mb-1">${message}</p>`;
        historyLog.scrollTop = historyLog.scrollHeight;
    }

    function updateUI() {
        const currentPlayer = players[currentPlayerIndex];
        const isMyTurn = currentPlayer.id === myPlayerId;

        if (gameState === 'waiting_for_roll') {
            rollButton.disabled = !isMyTurn;
            if (isMyTurn) {
                turnMessage.innerHTML = "<strong>¡Es tu turno!</strong> Lanza el dado.";
            } else {
                turnMessage.innerHTML = `Es el turno de <strong class="${currentPlayer.colorClass}">${currentPlayer.name}</strong> de lanzar el dado.`;
            }
        } else if (gameState === 'placing_dino') {
            rollButton.disabled = true;
        }
    }

    function advanceTurn() {
        currentPlayerIndex = (currentPlayerIndex + 1) % players.length;
        gameState = 'waiting_for_roll';
        updateUI();
    }

    // --- Lógica de Animación 3D ---
    const container = document.getElementById('dice-container');
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    container.appendChild(renderer.domElement);
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
    scene.add(ambientLight);
    const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
    directionalLight.position.set(5, 10, 7.5);
    scene.add(directionalLight);
    const loader = new THREE.TextureLoader();
    const materials = [
        new THREE.MeshLambertMaterial({ map: loader.load('../public/img/bosque.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('../public/img/banos.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('../public/img/vacio.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('../public/img/t-rex.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('../public/img/rocas.png') }),
        new THREE.MeshLambertMaterial({ map: loader.load('../public/img/cafeteria.png') })
    ];
    const geometry = new THREE.BoxGeometry();
    const dice = new THREE.Mesh(geometry, materials);
    scene.add(dice);
    camera.position.z = 1.8;
    let isRolling = false;

    function animate(time) {
        requestAnimationFrame(animate);
        if (typeof TWEEN !== 'undefined') {
            TWEEN.update(time);
        }
        renderer.render(scene, camera);
    }

    function rollDice(forcedResult) {
        if (isRolling) return;
        const isActionAllowed = players[currentPlayerIndex].id === myPlayerId && gameState === 'waiting_for_roll';
        if (!isActionAllowed) return;

        isRolling = true;
        rollButton.disabled = true;

        const result = forcedResult || Math.floor(Math.random() * 6) + 1;
        const restrictionText = diceRestrictions[result - 1];
        const currentPlayer = players[currentPlayerIndex];

        const targetRotations = {
            1: { x: 0, y: Math.PI / 2, z: 0 }, 
            2: { x: -Math.PI / 2, y: 0, z: 0 },
            3: { x: 0, y: 0, z: 0 }, 
            4: { x: 0, y: Math.PI, z: 0 },
            5: { x: Math.PI / 2, y: 0, z: 0 }, 
            6: { x: 0, y: -Math.PI / 2, z: 0 }
        };

        const finalRotation = {
            x: targetRotations[result].x + (Math.PI * 4),
            y: targetRotations[result].y + (Math.PI * 4),
            z: targetRotations[result].z + (Math.PI * 4)
        };

        // Verificar si TWEEN está disponible antes de usarlo
        if (typeof TWEEN !== 'undefined') {
            new TWEEN.Tween(dice.rotation)
                .to(finalRotation, 1500)
                .easing(TWEEN.Easing.Cubic.Out)
                .onComplete(() => {
                    isRolling = false;
                    gameState = 'placing_dino';

                    if (currentPlayer.id === myPlayerId) {
                        turnMessage.innerHTML = `Debes colocar un dinosaurio <strong>${restrictionText}</strong>.`;
                    } else {
                        turnMessage.innerHTML = `<strong class="${currentPlayer.colorClass}">${currentPlayer.name}</strong> debe colocar un dinosaurio <strong>${restrictionText}</strong>.`;
                    }

                    addHistory(`<strong class="${currentPlayer.colorClass}">${currentPlayer.name}</strong> ha sacado: ${restrictionText}.`);

                    dice.rotation.x = targetRotations[result].x;
                    dice.rotation.y = targetRotations[result].y;
                    dice.rotation.z = targetRotations[result].z;

                    aplicarResultadoDado(result);
                })
                .start();
        } else {
            // Fallback si TWEEN no está disponible
            console.warn('TWEEN.js no está disponible');
            dice.rotation.x = targetRotations[result].x;
            dice.rotation.y = targetRotations[result].y;
            dice.rotation.z = targetRotations[result].z;
            
            isRolling = false;
            gameState = 'placing_dino';
            aplicarResultadoDado(result);
        }
    }

    rollButton.addEventListener('click', () => rollDice());

    // --- Inicialización del Juego y Modal ---
    function setupPlayerCards() {
        players.forEach((player, index) => {
            const card = document.getElementById(`player-card-${index}`);
            if (card) {
                card.innerHTML = `
                    <div class="card-body text-center d-flex flex-column">
                        <p class="fw-semibold ${player.colorClass} mb-1">${player.name}</p>
                        <p class="fw-bold mb-2 small">Puntos: <span class="h5 fw-bold">0</span></p>
                        <div class="row g-2 mt-auto">
                            <div class="col-4 text-center"><p class="fw-bold mb-0 small">0</p><img src="../public/img/triceratops.png" alt="Triceratops" class="dino-score-icon"></div>
                            <div class="col-4 text-center"><p class="fw-bold mb-0 small">0</p><img src="../public/img/t-rex.png" alt="T-Rex" class="dino-score-icon"></div>
                            <div class="col-4 text-center"><p class="fw-bold mb-0 small">0</p><img src="../public/img/stegosaurus.png" alt="Stegosaurus" class="dino-score-icon"></div>
                            <div class="col-4 text-center"><p class="fw-bold mb-0 small">0</p><img src="../public/img/pterodactilo.png" alt="Pterodáctilo" class="dino-score-icon"></div>
                            <div class="col-4 text-center"><p class="fw-bold mb-0 small">0</p><img src="../public/img/plesiosaurio.png" alt="Plesiosaurio" class="dino-score-icon"></div>
                            <div class="col-4 text-center"><p class="fw-bold mb-0 small">0</p><img src="../public/img/brachiosaurus.png" alt="Brachiosaurus" class="dino-score-icon"></div>
                        </div>
                    </div>
                `;
            }
        });
    }

    const profileModal = document.getElementById('profileModal');
    if (profileModal) {
        profileModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const playerId = parseInt(button.getAttribute('data-player-id'));
            const profileData = playerProfiles.find(p => p.id === playerId);

            if (profileData) {
                profileModal.querySelector('.modal-title').textContent = `Perfil de ${profileData.name}`;
                profileModal.querySelector('#profile-pic').src = profileData.pfp;
                profileModal.querySelector('#profile-name').textContent = profileData.name;
                profileModal.querySelector('#profile-games').textContent = profileData.games;
                profileModal.querySelector('#profile-wins').textContent = profileData.wins;
                profileModal.querySelector('#profile-desc').textContent = profileData.desc;
            }
        });
    }

    const pauseModal = document.getElementById('pauseModal');
    if (pauseModal) {
        pauseModal.addEventListener('show.bs.modal', function () {
            isPaused = true;
            clearTimeout(gameLoopTimeout);
            addHistory(`<strong class="${players[myPlayerId].colorClass}">${players[myPlayerId].name}</strong> ha pausado la partida.`);
        });
        pauseModal.addEventListener('hide.bs.modal', function () {
            isPaused = false;
            addHistory(`<strong class="${players[myPlayerId].colorClass}">${players[myPlayerId].name}</strong> ha reanudado la partida.`);
            gameLoop();
        });
    }

    const abandonBtn = document.getElementById('abandon-btn');
    if (abandonBtn) {
        abandonBtn.addEventListener('click', () => {
            addHistory(`<strong class="text-danger">${players[myPlayerId].name} ha abandonado la partida.</strong>`);
        });
    }

    function gameLoop() {
        if (isPaused) return;

        const currentPlayer = players[currentPlayerIndex];
        const isMyTurn = currentPlayer.id === myPlayerId;

        if (!isMyTurn) {
            gameLoopTimeout = setTimeout(() => {
                rollDice();
                gameLoopTimeout = setTimeout(() => {
                    addHistory(`<strong class="${currentPlayer.colorClass}">${currentPlayer.name}</strong> ha colocado un dinosaurio.`);
                    advanceTurn();
                    gameLoop();
                }, 3000);
            }, 2000);
        }
    }

    // ======================
    // 🎲 LÓGICA DE RECINTOS
    // ======================
    const recintos = {
        bosque: { tipo: "bosque", regla: "misma_especie" },
        prado: { tipo: "llanura", regla: "todas_distintas" },
        amor: { tipo: "llanura", regla: "parejas" },
        trio: { tipo: "bosque", regla: "exactamente_3" },
        rey: { tipo: "llanura", regla: "unico_especie" },
        isla: { tipo: "bosque", regla: "unico_dino" }
    };

    function habilitarRecintos(restriccion) {
        const zones = document.querySelectorAll('.drop-zone');
        zones.forEach(zone => {
            const recinto = recintos[zone.dataset.recinto];
            if (cumpleRestriccion(recinto, restriccion, zone)) {
                zone.classList.add("habilitado");
                zone.classList.remove("bloqueado");
            } else {
                zone.classList.remove("habilitado");
                zone.classList.add("bloqueado");
            }
        });
    }

    function cumpleRestriccion(recinto, restriccion, zone) {
        switch (restriccion) {
            case "bosque": return recinto.tipo === "bosque";
            case "llanura": return recinto.tipo === "llanura";
            case "banos": return isRightSide(zone);
            case "cafeteria": return !isRightSide(zone);
            case "vacio": return zone.children.length === 0;
            case "sin_trex": return !contieneTrex(zone);
            default: return true;
        }
    }

    function isRightSide(zone) {
        return zone.style.right && zone.style.right !== "";
    }

    function contieneTrex(zone) {
        return [...zone.children].some(dino => dino.alt === "T-Rex");
    }

    function aplicarResultadoDado(resultado) {
        const restricciones = ["bosque", "llanura", "banos", "cafeteria", "vacio", "sin_trex"];
        const restriccionDado = restricciones[resultado - 1];
        habilitarRecintos(restriccionDado);
    }

    // ======================
    // 🦖 DRAG & DROP
    // ======================
    const dinos = document.querySelectorAll('.dino-meeple');
    const dropZones = document.querySelectorAll('.drop-zone');
    let draggedDino = null;

    dinos.forEach(dino => {
        dino.addEventListener('dragstart', e => {
            if (draggedDino) {
                e.preventDefault();
                return;
            }
            draggedDino = dino;
            e.dataTransfer.setData("text/plain", dino.id);
            e.dataTransfer.effectAllowed = "move";
            setTimeout(() => { dino.style.display = "none"; }, 0);
        });

        dino.addEventListener('dragend', () => {
            setTimeout(() => {
                if (draggedDino) draggedDino.style.display = "block";
                draggedDino = null;
            }, 0);
        });
    });

    dropZones.forEach(zone => {
        zone.addEventListener('dragover', e => {
            if (!zone.classList.contains("habilitado")) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = "move";
            zone.classList.add('highlight');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('highlight');
        });

        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('highlight');
            const dinoId = e.dataTransfer.getData("text/plain");

            if (draggedDino && dinoId && zone.classList.contains("habilitado")) {
                const placed = draggedDino.cloneNode(true);
                placed.removeAttribute("id");
                placed.classList.add("placed-dino");
                placed.setAttribute("draggable", "false");
                zone.appendChild(placed);
                draggedDino.remove();
                draggedDino = null;

                // --- Avanzar turno después de colocar ---
                setTimeout(() => {
                    addHistory(`<strong class="${players[currentPlayerIndex].colorClass}">${players[currentPlayerIndex].name}</strong> ha colocado un dinosaurio.`);
                    advanceTurn();
                    document.querySelectorAll('.drop-zone').forEach(z => z.classList.remove('habilitado'));
                }, 100);
            }
        });
    });

    // Inicializar el juego
    setupPlayerCards();
    updateUI();
    animate();
    gameLoop();
});