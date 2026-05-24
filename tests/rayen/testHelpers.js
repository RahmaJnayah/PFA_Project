// jest test helper for DOM and global setup
// Provides utilities for setting up test DOM, mocking globals, and managing test state

const util = require('util');

// Polyfill TextEncoder/TextDecoder if needed
function polyfillTextEncoders() {
    if (typeof global.TextEncoder === 'undefined' && util.TextEncoder) {
        global.TextEncoder = util.TextEncoder;
    }
    if (typeof global.TextDecoder === 'undefined' && util.TextDecoder) {
        global.TextDecoder = util.TextDecoder;
    }
}

// Setup DOM environment (relies on Jest's jsdom testEnvironment)
function setupDOM(html = '<!doctype html><html><body></body></html>') {
    polyfillTextEncoders();
    
    if (typeof global.window === 'undefined') {
        throw new Error('No global `window` present. Ensure Jest is configured with testEnvironment: "jsdom"');
    }
    
    // Clear body
    document.body.innerHTML = '';
    
    // Set up minimal localStorage mock if needed
    if (!global.localStorage) {
        global.localStorage = {
            store: {},
            getItem(key) { return this.store[key] || null; },
            setItem(key, val) { this.store[key] = String(val); },
            removeItem(key) { delete this.store[key]; },
            clear() { this.store = {}; }
        };
    }
    
    // Set up minimal sessionStorage mock
    if (!global.sessionStorage) {
        global.sessionStorage = {
            store: {},
            getItem(key) { return this.store[key] || null; },
            setItem(key, val) { this.store[key] = String(val); },
            removeItem(key) { delete this.store[key]; },
            clear() { this.store = {}; }
        };
    }
}

// Teardown DOM environment
function teardownDOM() {
    if (global.localStorage) {
        global.localStorage.clear();
    }
    if (global.sessionStorage) {
        global.sessionStorage.clear();
    }
    if (document) {
        document.body.innerHTML = '';
    }
}

// Mock fetch for API testing
function createFetchMock(defaultResponse = { status: 'success', data: {} }) {
    return jest.fn((url, options) => {
        return Promise.resolve({
            ok: true,
            status: 200,
            json: () => Promise.resolve(defaultResponse),
            text: () => Promise.resolve(JSON.stringify(defaultResponse))
        });
    });
}

// Mock navigator.sendBeacon
function createSendBeaconMock() {
    return jest.fn((url, data) => true);
}

module.exports = {
    setupDOM,
    teardownDOM,
    createFetchMock,
    createSendBeaconMock
};

// Simple script loader for browser JS files used in tests
const path = require('path');
const fs = require('fs');
const _loaded = new Set();

function _normalizePath(p) {
    // Allow paths that include the repository prefix produced by some tests
    const prefix = 'PRO-PFE-YOPY/yopy-platform/';
    if (p.startsWith(prefix)) {
        p = p.slice(prefix.length);
    }
    // If path is already absolute, return as-is, else resolve from project root
    if (path.isAbsolute(p)) return p;
    return path.resolve(process.cwd(), p);
}

function loadScript(p) {
    const abs = _normalizePath(p);
    if (_loaded.has(abs)) return;
    if (!fs.existsSync(abs)) {
        throw new Error(`Script not found: ${abs}`);
    }
    const src = fs.readFileSync(abs, 'utf8');
    // Execute in a VM context seeded from the real global. After execution
    // copy any new top-level declarations back to the real global so tests
    // can reference classes/vars by name (e.g. `BehaviorCollector`).
    const vm = require('vm');
    // Seed with a shallow copy so object references (fetch, navigator, etc.)
    // remain the same as on the real global while still allowing us to
    // detect newly created top-level names.
    const context = vm.createContext(Object.assign({}, global));
    const before = new Set(Object.getOwnPropertyNames(context));
    const script = new vm.Script(src, { filename: abs });
    script.runInContext(context);
    const after = Object.getOwnPropertyNames(context);
    for (const name of after) {
        if (!before.has(name)) {
            try {
                global[name] = context[name];
            } catch (e) {
                // best-effort: ignore non-writable globals
            }
        }
    }
    // Fallbacks: 1) parse top-level `class`/`function` declarations and 2)
    // attempt to resolve them from the VM context using `vm.runInContext`.
    // Some bindings are lexical in the VM and not reachable via the context
    // object itself, so `runInContext(name, context)` returns the value.
    try {
        const declRe = /(?:^|\n)\s*(?:class|function)\s+([A-Za-z_$][0-9A-Za-z_$]*)/g;
        let m;
        while ((m = declRe.exec(src)) !== null) {
            const name = m[1];
            let val;
            try {
                val = vm.runInContext(name, context);
            } catch (e) {
                val = context[name];
            }
            if (val !== undefined && global[name] === undefined) {
                try { global[name] = val; } catch (e) {}
            }
        }
    } catch (e) {}
    _loaded.add(abs);
}

module.exports.loadScript = loadScript;
