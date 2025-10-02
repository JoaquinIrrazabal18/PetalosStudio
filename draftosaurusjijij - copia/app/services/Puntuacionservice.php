<?php
/**
 * app/services/PuntuacionService.php
 * Contiene toda la lógica para calcular la puntuación de un tablero.
 * Lógica traducida desde la implementación del frontend y validada con el EsRe.
 */

class PuntuacionService {

    /**
     * Calcula la puntuación "en vivo" de un único tablero, sin contar
     * los recintos que dependen de otros jugadores.
     */
    public function calculateLiveScore(array $board, string $boardType): int {
        $totalScore = 0;

        if ($boardType === 'Verano') {
            $totalScore += $this->calcBosqueSemejanza($board['Bosque de la Semejanza'] ?? []);
            $totalScore += $this->calcPradoDiferencia($board['Prado de la Diferencia'] ?? []);
            $totalScore += $this->calcPraderaAmor($board['Pradera del Amor'] ?? []);
            $totalScore += $this->calcTrioFrondoso($board['Trío Frondoso'] ?? []);
            $totalScore += $this->calcIslaSolitaria($board['Isla Solitaria'] ?? [], $board);
        } else { // Invierno
            $totalScore += $this->calcBosqueOrdenado($board['Bosque Ordenado'] ?? []);
            $totalScore += $this->calcPuenteEnamorados($board['Puente de los Enamorados Izquierda'] ?? [], $board['Puente de los Enamorados Derecha'] ?? []);
            $totalScore += $this->calcPiramide($board['La Pirámide'] ?? []);
        }

        $totalScore += $this->calcRio($board['Rio'] ?? []);
        $totalScore += $this->calcBonoTrex($board);

        return $totalScore;
    }
    
    /**
     * Calcula las puntuaciones finales para todos los jugadores.
     */
    public function calculateFinalScores(array $allPlayerBoards, string $boardType, array $playerIds): array {
        $finalScores = [];

        foreach ($playerIds as $playerId) {
            $playerBoard = $allPlayerBoards[$playerId] ?? [];
            $score = $this->calculateLiveScore($playerBoard, $boardType);
            
            if ($boardType === 'Verano') {
                $score += $this->calcReyDeLaSelva($playerId, $allPlayerBoards);
            } else { // Invierno
                $score += $this->calcPuestoObservacion($playerId, $allPlayerBoards, $playerIds);
            }
            
            $allDinosOnBoard = !empty($playerBoard) ? array_merge(...array_values($playerBoard)) : [];
            $trexCount = count(array_filter($allDinosOnBoard, fn($d) => $d['especie'] === 'T-Rex'));

            $finalScores[$playerId] = [
                'id' => $playerId,
                'score' => $score,
                'trexCount' => $trexCount
            ];
        }
        return $finalScores;
    }

    // --- MÉTODOS DE CÁLCULO (Tablero de Verano) ---

    private function calcBosqueSemejanza(array $dinos): int { // RFD17
        $map = [1 => 1, 2 => 4, 3 => 8, 4 => 12, 5 => 18, 6 => 24];
        return $map[count($dinos)] ?? 0;
    }

    private function calcPradoDiferencia(array $dinos): int { // RFD20
        $map = [1 => 1, 2 => 3, 3 => 6, 4 => 10, 5 => 15, 6 => 21];
        return $map[count($dinos)] ?? 0;
    }
    
    private function calcPraderaAmor(array $dinos): int { // RFD22
        if (empty($dinos)) return 0;
        $counts = array_count_values(array_column($dinos, 'especie'));
        $score = 0;
        foreach ($counts as $count) {
            $score += floor($count / 2) * 5;
        }
        return $score;
    }

    private function calcTrioFrondoso(array $dinos): int { // RFD26
        return count($dinos) === 3 ? 7 : 0;
    }

    private function calcIslaSolitaria(array $dinos, array $fullBoard): int { // RFD32
        if (count($dinos) !== 1) return 0;
        $dinoEnIsla = $dinos[0];
        $totalCount = 0;
        foreach ($fullBoard as $recinto) {
            foreach ($recinto as $d) {
                if ($d['especie'] === $dinoEnIsla['especie']) {
                    $totalCount++;
                }
            }
        }
        return $totalCount === 1 ? 7 : 0;
    }
    
    private function calcReyDeLaSelva(int $playerId, array $allBoards): int { // RFD29
        $myBoard = $allBoards[$playerId] ?? [];
        if (empty($myBoard['Rey de la Selva'])) return 0;
        
        $myDinoSpecies = $myBoard['Rey de la Selva'][0]['especie'];
        
        $flattenedMyBoard = !empty($myBoard) ? array_merge(...array_values($myBoard)) : [];
        $myCount = count(array_filter($flattenedMyBoard, fn($d) => $d['especie'] === $myDinoSpecies));

        foreach ($allBoards as $otherPlayerId => $otherBoard) {
            if ($playerId === $otherPlayerId) continue;
            
            $flattenedOtherBoard = !empty($otherBoard) ? array_merge(...array_values($otherBoard)) : [];
            $otherCount = count(array_filter($flattenedOtherBoard, fn($d) => $d['especie'] === $myDinoSpecies));

            if ($otherCount > $myCount) return 0;
        }
        return 7;
    }
    
    // --- MÉTODOS DE CÁLCULO (Tablero de Invierno) ---

    private function calcBosqueOrdenado(array $dinos): int { // RFD37
        $map = [1 => 2, 2 => 4, 3 => 8, 4 => 12, 5 => 18, 6 => 24];
        return $map[count($dinos)] ?? 0;
    }
    
    private function calcPuenteEnamorados(array $dinosIzq, array $dinosDer): int { // RFD40
        if (empty($dinosIzq) || empty($dinosDer)) return 0;
        $countsIzq = array_count_values(array_column($dinosIzq, 'especie'));
        $countsDer = array_count_values(array_column($dinosDer, 'especie'));
        $score = 0;
        foreach ($countsIzq as $especie => $count) {
            if (isset($countsDer[$especie])) {
                $score += min($count, $countsDer[$especie]) * 6;
            }
        }
        return $score;
    }

    private function calcPiramide(array $dinos): int { // RFD44
        $score = 0;
        foreach ($dinos as $dino) {
            $pos = $dino['posicion'];
            if ($pos <= 2) $score += 1;
            else if ($pos <= 4) $score += 2;
            else if ($pos === 5) $score += 3;
        }
        return $score;
    }
    
    private function calcPuestoObservacion(int $playerId, array $allBoards, array $playerIds): int { // RFD46
        $myBoard = $allBoards[$playerId] ?? [];
        if (empty($myBoard['Puesto de Observación'])) return 0;
        
        $observedSpecies = $myBoard['Puesto de Observación'][0]['especie'];
        
        $playerIndex = array_search($playerId, $playerIds);
        if ($playerIndex === false) return 0;
        
        $rightPlayerIndex = ($playerIndex + 1) % count($playerIds);
        $rightPlayerId = $playerIds[$rightPlayerIndex];
        $rightPlayerBoard = $allBoards[$rightPlayerId] ?? [];
        
        if (empty($rightPlayerBoard)) return 0;
        
        $flattenedRightBoard = array_merge(...array_values($rightPlayerBoard));
        $countOnRight = count(array_filter($flattenedRightBoard, fn($d) => $d['especie'] === $observedSpecies));
        
        return $countOnRight * 2;
    }

    // --- MÉTODOS DE CÁLCULO (Comunes) ---

    private function calcRio(array $dinos): int { // RFD51
        return count($dinos);
    }
    
    private function calcBonoTrex(array $board): int { // RFD52
        $recintosConTrex = [];
        foreach($board as $nombreRecinto => $dinos) {
            if ($nombreRecinto === 'Rio') continue;
            if (in_array('T-Rex', array_column($dinos, 'especie'))) {
                $recintosConTrex[$nombreRecinto] = true;
            }
        }
        return count($recintosConTrex);
    }
}

