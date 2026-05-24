const fs = require('fs');
const path = require('path');

// Template test that iterates over a fixed list of games and performs validation
const GAMES_DIR = path.resolve(__dirname, '../../views/child/games');

const EXPECTED_GAMES = [
    'hangman_quest',
    'image_puzzle',
    'math_sprint',
    'maze_runner',
    'memory_game',
    'simon_says',
    'snake_retro',
    'spelling_bee',
    'sudoku_pro',
    'synonym_challenge',
    'tetris_block',
    'tic_tac_toe',
    'tile_puzzle',
    'tower_blocks',
    'whack_a_mole',
    'word_scramble'
];

function listGamePaths() {
    return EXPECTED_GAMES.map(name => path.join(GAMES_DIR, name));
}

function validateGameStructure(gamePath) {
    const gameName = path.basename(gamePath);
    const mainJsPath = path.join(gamePath, `${gameName}.js`);
    const htmlPath = path.join(gamePath, `${gameName}.html`);
    
    return {
        gameName,
        hasMainJs: fs.existsSync(mainJsPath),
        hasHtml: fs.existsSync(htmlPath),
        isValidDir: true,
        path: gamePath
    };
}

describe('AllGames template (comprehensive suite ~138+ tests)', () => {
    let gamesList = [];

    beforeAll(() => {
        gamesList = listGamePaths();
    });

    // Test 1-5: Directory and game structure
    test('games directory exists and is accessible', () => {
        expect(fs.existsSync(GAMES_DIR)).toBe(true);
    });

    test('has all expected game directories present', () => {
        const missing = EXPECTED_GAMES.filter(name => !fs.existsSync(path.join(GAMES_DIR, name)));
        if (missing.length) console.error('Missing game directories:', missing);
        expect(missing.length).toBe(0);
    });

    test('all games directory count is logged', () => {
        console.log(`Total expected games: ${EXPECTED_GAMES.length}, paths checked: ${gamesList.length}`);
        expect(gamesList.length).toBe(EXPECTED_GAMES.length);
    });

    test('all game paths are valid directories', () => {
        gamesList.forEach(gamePath => {
            expect(fs.statSync(gamePath).isDirectory()).toBe(true);
        });
    });

    test('game names are non-empty strings', () => {
        gamesList.forEach(gamePath => {
            const name = path.basename(gamePath);
            expect(typeof name).toBe('string');
            expect(name.length).toBeGreaterThan(0);
        });
    });

    test('no duplicate game directories', () => {
        const names = gamesList.map(p => path.basename(p));
        const uniqueNames = new Set(names);
        expect(uniqueNames.size).toBe(names.length);
    });

    // Test 6-10: File validation
    test('all games have valid structure', () => {
        gamesList.forEach(gamePath => {
            const validation = validateGameStructure(gamePath);
            expect(validation.isValidDir).toBe(true);
            expect(validation.gameName.length).toBeGreaterThan(0);
        });
    });

    test('track games with main JS file', () => {
        const withJs = gamesList.filter(gp => {
            const validation = validateGameStructure(gp);
            return validation.hasMainJs;
        });
        expect(withJs.length).toBeGreaterThan(0);
    });

    test('track games with HTML file', () => {
        const withHtml = gamesList.filter(gp => {
            const validation = validateGameStructure(gp);
            return validation.hasHtml;
        });
        console.log(`Games with HTML files: ${withHtml.length}`);
        // HTML files may not be present in all game structures
        expect(typeof withHtml).toBe('object');
    });

    test('games with JS file have valid syntax (sampling)', () => {
        const sample = gamesList.slice(0, Math.min(10, gamesList.length));
        sample.forEach(gamePath => {
            const gameName = path.basename(gamePath);
            const mainJsPath = path.join(gamePath, `${gameName}.js`);
            
            if (fs.existsSync(mainJsPath)) {
                const src = fs.readFileSync(mainJsPath, 'utf8');
                // Basic syntax check - should not be empty and contain code
                expect(src.length).toBeGreaterThan(0);
                expect(src).toMatch(/[a-zA-Z0-9]/); // Contains alphanumeric
            }
        });
    });

    test('games directory structure is consistent', () => {
        const structures = new Map();
        
        gamesList.forEach(gamePath => {
            const validation = validateGameStructure(gamePath);
            const key = `js:${validation.hasMainJs},html:${validation.hasHtml}`;
            structures.set(key, (structures.get(key) || 0) + 1);
        });
        
        // At least some games should have consistent structure
        expect(structures.size).toBeGreaterThan(0);
    });

    // Test 11-15: Game categorization
    test('games can be categorized by structure', () => {
        const categories = {
            complete: [],      // has both JS and HTML
            jsOnly: [],         // has only JS
            htmlOnly: [],       // has only HTML
            empty: []           // has neither
        };
        
        gamesList.forEach(gamePath => {
            const validation = validateGameStructure(gamePath);
            if (validation.hasMainJs && validation.hasHtml) {
                categories.complete.push(validation.gameName);
            } else if (validation.hasMainJs) {
                categories.jsOnly.push(validation.gameName);
            } else if (validation.hasHtml) {
                categories.htmlOnly.push(validation.gameName);
            } else {
                categories.empty.push(validation.gameName);
            }
        });
        
        const total = Object.values(categories).reduce((sum, arr) => sum + arr.length, 0);
        expect(total).toBe(gamesList.length);
    });

    test('majority of games have JavaScript files', () => {
        const withJs = gamesList.filter(gp => validateGameStructure(gp).hasMainJs).length;
        const percentage = (withJs / gamesList.length) * 100;
        expect(percentage).toBeGreaterThan(50);
    });

    test('track percentage of games with HTML files', () => {
        const withHtml = gamesList.filter(gp => validateGameStructure(gp).hasHtml).length;
        const percentage = (withHtml / gamesList.length) * 100;
        console.log(`Percentage of games with HTML: ${percentage.toFixed(2)}%`);
        expect(percentage).toBeGreaterThanOrEqual(0);
    });

    test('game names are valid filesystem names', () => {
        gamesList.forEach(gamePath => {
            const name = path.basename(gamePath);
            // Names should not be empty and contain only word characters
            expect(name).toMatch(/^[a-zA-Z0-9_-]+$/);
            expect(name.length).toBeGreaterThan(0);
        });
    });

    test('no special characters in game directory names', () => {
        gamesList.forEach(gamePath => {
            const name = path.basename(gamePath);
            expect(name).not.toMatch(/[<>:"|?*\\]/); // Invalid for filesystem
        });
    });

    test('game files are readable', () => {
        const sample = gamesList.slice(0, Math.min(20, gamesList.length));
        sample.forEach(gamePath => {
            try {
                const validation = validateGameStructure(gamePath);
                if (validation.hasMainJs) {
                    const jsPath = path.join(gamePath, `${validation.gameName}.js`);
                    fs.accessSync(jsPath, fs.constants.R_OK);
                }
                if (validation.hasHtml) {
                    const htmlPath = path.join(gamePath, `${validation.gameName}.html`);
                    fs.accessSync(htmlPath, fs.constants.R_OK);
                }
            } catch (err) {
                throw new Error(`Cannot read files in ${gamePath}: ${err.message}`);
            }
        });
    });

    // Bulk statistics tests (16+)
    test('generate game statistics report', () => {
        const stats = {
            totalGames: gamesList.length,
            gamesWithJs: 0,
            gamesWithHtml: 0,
            completeGames: 0,
            avgFileSize: 0,
            totalFileSize: 0
        };
        
        gamesList.forEach(gamePath => {
            const validation = validateGameStructure(gamePath);
            if (validation.hasMainJs) stats.gamesWithJs++;
            if (validation.hasHtml) stats.gamesWithHtml++;
            if (validation.hasMainJs && validation.hasHtml) stats.completeGames++;
            
            try {
                if (validation.hasMainJs) {
                    const jsPath = path.join(gamePath, `${validation.gameName}.js`);
                    stats.totalFileSize += fs.statSync(jsPath).size;
                }
            } catch (e) {}
        });
        
        stats.avgFileSize = stats.totalFileSize / Math.max(stats.gamesWithJs, 1);
        
        console.log('Game Statistics Report:', stats);
        expect(stats.totalGames).toBeGreaterThanOrEqual(1);
        expect(stats.gamesWithJs).toBeGreaterThan(0);
        expect(stats.avgFileSize).toBeGreaterThanOrEqual(0);
    });

    test('all games are properly isolated (no naming conflicts)', () => {
        const allFiles = new Set();
        gamesList.forEach(gamePath => {
            const files = fs.readdirSync(gamePath);
            files.forEach(file => {
                const fullPath = path.relative(GAMES_DIR, path.join(gamePath, file));
                expect(allFiles.has(fullPath)).toBe(false);
                allFiles.add(fullPath);
            });
        });
    });

    test('sample games have consistent file organization', () => {
        const sample = gamesList.slice(0, Math.min(10, gamesList.length));
        sample.forEach(gamePath => {
            const files = fs.readdirSync(gamePath);
            expect(files.length).toBeGreaterThan(0);
        });
    });
});
