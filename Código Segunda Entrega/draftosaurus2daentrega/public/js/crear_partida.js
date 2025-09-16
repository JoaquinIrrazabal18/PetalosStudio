document.getElementById("createGameForm").addEventListener("submit", function (e) {
    e.preventDefault();

    // Guardamos los datos en localStorage
    localStorage.setItem("gameName", document.getElementById("gameName").value);
    localStorage.setItem("hostName", document.getElementById("hostName").value);
    localStorage.setItem("playerCount", document.getElementById("playerCount").value);
    localStorage.setItem("gamePassword", document.getElementById("gamePassword").value);

    // Redirigimos al juego
    window.location.href = "../i";
});
