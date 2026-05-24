/**
 * @jest-environment jsdom
 *
 * memory_game.test.js — complete test suite
 *
 * WHY @jest-environment jsdom
 * ────────────────────────────────────────────────────────────────────────────
 * memory_game.js references document, canvas, sessionStorage and navigator at
 * the top level of its IIFE. Jest's default environment is "node" which has
 * none of these globals — every test fails with "document is not defined"
 * before any assertion runs. This single docblock switches the environment to
 * jsdom for this file only, with no changes required to jest.config.js.
 *
 * SANDBOX CONSTRAINTS — read before editing
 * ────────────────────────────────────────────────────────────────────────────
 * testHelpers.js executes each script EXACTLY ONCE per Jest worker via a
 * module-level `_loaded` Set. Every loadScript() call after the first is a
 * no-op. This has three hard consequences:
 *
 * 1. OBJECT GLOBALS (gsap, fetch, collector)
 *    The vm sandbox is seeded via Object.assign({}, global) at load time so
 *    it holds the *same object references* that existed on global then.
 *    • Mutating a property on that object IS visible inside the script.
 *    • Assigning global.gsap = newObj AFTER load is NOT visible.
 *    → Create shared stub objects ONCE before loadScript; mutate their
 *      properties in beforeEach.
 *
 * 2. CLOSURE VARIABLES (first, lock, pairs, errors, firstFlipTime)
 *    Declared with `let` inside the IIFE — unreachable via global.* after load.
 *    → Observe state indirectly:
 *      • pairs / errors  → HUD DOM text written by updateHUD()
 *      • lock            → whether a third flip is blocked
 *      • first           → whether collector.record fires on a lone flip
 *
 * 3. FAKE TIMERS
 *    jest.useFakeTimers() MUST be called before loadScript so the sandbox
 *    captures Jest's fake setTimeout / clearInterval.
 *    afterEach calls jest.runAllTimers() to drain the mismatch cleanup
 *    setTimeout(300) and guarantee lock=false / first=null for the next test.
 *
 * 4. FETCH
 *    global.fetch must be set before loadScript. Afterwards use
 *    fetchMock.mockImplementation() — same reference, sandbox sees it.
 *
 * 5. COLLECTOR WRAPPING
 *    The real collector.record is snapshotted once in beforeAll.
 *    Each beforeEach wraps THAT snapshot in a fresh jest.fn() so call counts
 *    never accumulate across tests.
 */

'use strict';

const { loadScript } = require('./testHelpers');

// ── Shared stubs — created once, properties mutated per test ─────────────────

const gsapStub = {
  from:   () => {},
  // fires onComplete synchronously so the mismatch cleanup setTimeout(300)
  // is always registered in the same tick — jest.advanceTimersByTime(300) fires it
  to:     (_targets, opts) => { if (opts?.onComplete) opts.onComplete(); },
  fromTo: () => {},
  set:    () => {},
  timeline: () => ({ to: () => {}, from: () => {}, call: () => {} }),
};

let fetchMock;

// ── Suite ─────────────────────────────────────────────────────────────────────

describe('Memory Game Logic', () => {
  let collector;
  let _originalRecord;
  let flip;
  let submitScore;

  // ── One-time bootstrap ────────────────────────────────────────────────────
  // Order: fake timers → DOM → stubs on global → loadScript

  beforeAll(() => {
    jest.useFakeTimers();

    document.body.innerHTML = `
      <canvas id="bg-canvas"></canvas>
      <div id="buddy-avatar"></div>
      <div id="game-grid"></div>
      <div id="hud-pairs"></div>
      <div id="hud-errors"></div>
      <div id="hud-time"></div>
      <div id="progress-fill"></div>
      <div id="result-overlay"></div>
      <div id="res-pairs"></div>
      <div id="res-time"></div>
      <div id="res-errors"></div>
    `;

    document.getElementById('bg-canvas').getContext = () => ({
      clearRect: () => {},
      arc:       () => {},
      fill:      () => {},
      beginPath: () => {},
    });

    // Assign shared stubs — sandbox snapshots these exact references at load time
    global.gsap = gsapStub;
    // No-op rAF prevents animateBg()'s recursive loop from flooding fake timers
    global.requestAnimationFrame = () => {};

    // fetch must exist before loadScript so the sandbox captures it
    fetchMock = jest.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve({ status: 'success', data: { points: 0 } }),
      })
    );
    global.fetch = fetchMock;

    loadScript('PRO-PFE-YOPY/yopy-platform/public/js/games/BehaviorCollector.js');
    loadScript('PRO-PFE-YOPY/yopy-platform/views/child/games/memory_game/memory_game.js');

    flip = global.flip || window.flip;
    submitScore = global.submitScore || window.submitScore;

    collector = global.collector || window.collector;
    if (!collector) {
      collector = { record: jest.fn(), flush: () => Promise.resolve() };
    }

    // Ensure both globals and local reference always target the same object
    global.collector = collector;
    window.collector = collector;

    // Snapshot once — beforeEach always wraps this, never a previous spy
    _originalRecord = typeof collector.record === 'function'
      ? collector.record.bind(collector)
      : jest.fn();
  });

  afterAll(() => {
    jest.useRealTimers();
  });

  // ── Per-test reset ────────────────────────────────────────────────────────

  beforeEach(() => {
    jest.clearAllMocks();
    jest.clearAllTimers();

    // Mutate shared gsap properties — never replace the object itself
    gsapStub.from     = () => {};
    gsapStub.to       = (_t, opts) => { if (opts?.onComplete) opts.onComplete(); };
    gsapStub.fromTo   = () => {};
    gsapStub.set      = () => {};
    gsapStub.timeline = () => ({ to: () => {}, from: () => {}, call: () => {} });

    // Reset fetch via the same reference the sandbox holds
    fetchMock.mockImplementation(() =>
      Promise.resolve({
        json: () => Promise.resolve({ status: 'success', data: { points: 0 } }),
      })
    );

    // Fresh spy each test — wraps the original, never the previous spy
    collector = global.collector || window.collector || collector;
    collector.record = jest.fn(_originalRecord);
    collector.flush  = jest.fn(() => Promise.resolve());
    global.collector = collector;
    window.collector = collector;
  });

  afterEach(() => {
    // Drain the mismatch cleanup setTimeout(300) so lock=false / first=null
    // before the next test regardless of what the current test did
    jest.runAllTimers();
  });

  // ── Helpers ───────────────────────────────────────────────────────────────

  /**
   * Returns a mock card whose classList is backed by a real Set so that
   * classList.contains() always returns an accurate value.
   */
  function createCard({ preFlipped = false, preMatched = false } = {}) {
    const classes = new Set();
    if (preFlipped) classes.add('flipped');
    if (preMatched) classes.add('matched');
    return {
      classList: {
        add:      jest.fn((...cs) => cs.forEach(c => classes.add(c))),
        remove:   jest.fn((...cs) => cs.forEach(c => classes.delete(c))),
        contains: jest.fn(c => classes.has(c)),
      },
      getBoundingClientRect: () => ({ left: 0, top: 0, width: 10, height: 10 }),
    };
  }

  // Read closure-owned counters from the HUD DOM (written by updateHUD())
  const hudPairs  = () => parseInt(document.getElementById('hud-pairs').textContent  || '0');
  const hudErrors = () => parseInt(document.getElementById('hud-errors').textContent || '0');
  const hudTime   = () => {
    const m = document.getElementById('hud-time').textContent.match(/(\d+):(\d+)/);
    return m ? parseInt(m[1]) * 60 + parseInt(m[2]) : 0;
  };

  // ── 1. Original regression tests ──────────────────────────────────────────

  describe('Core behavior — original tests', () => {
    it('records reaction time and success signal on a matching pair', () => {
      flip(createCard(), 'A');
      jest.advanceTimersByTime(100);
      flip(createCard(), 'A');

      expect(collector.record).toHaveBeenCalledWith('card_flip', expect.any(Number));
      expect(collector.record).toHaveBeenCalledWith('match',     expect.any(Number));
    });

    it('records reaction time and error signal on a mismatching pair', () => {
      flip(createCard(), 'A');
      jest.advanceTimersByTime(100);
      flip(createCard(), 'B');

      expect(collector.record).toHaveBeenCalledWith('card_flip', expect.any(Number));
      expect(collector.record).toHaveBeenCalledWith('mismatch',  null);
    });

    it('prevents flipping a third card while the mismatch lock is active', () => {
      flip(createCard(), 'A');
      flip(createCard(), 'B'); // mismatch → lock = true

      const card3 = createCard();
      flip(card3, 'C');

      expect(card3.classList.add).not.toHaveBeenCalled();
    });
  });

  // ── 2. Flip prevention ────────────────────────────────────────────────────

  describe('Flip prevention', () => {
    it('does not flip a card that is already flipped', () => {
      const card = createCard({ preFlipped: true });
      flip(card, 'A');
      expect(card.classList.add).not.toHaveBeenCalled();
    });

    it('does not flip a card that is already matched', () => {
      const card = createCard({ preMatched: true });
      flip(card, 'A');
      expect(card.classList.add).not.toHaveBeenCalled();
    });

    it('does not flip any card while the mismatch lock is active', () => {
      flip(createCard(), 'A');
      flip(createCard(), 'B'); // mismatch → lock = true

      const card3 = createCard();
      flip(card3, 'C');

      expect(card3.classList.add).not.toHaveBeenCalled();
    });
  });

  // ── 3. First-card behaviour ───────────────────────────────────────────────

  describe('First-card behaviour', () => {
    it('adds the "flipped" class to the first card of a pair', () => {
      const card = createCard();
      flip(card, 'A');
      expect(card.classList.add).toHaveBeenCalledWith('flipped');
    });

    it('does not record any collector event on the first card flip alone', () => {
      flip(createCard(), 'A');
      expect(collector.record).not.toHaveBeenCalled();
    });
  });

  // ── 4. Match outcome ──────────────────────────────────────────────────────

  describe('Match outcome', () => {
    it('adds the "matched" class to both cards', () => {
      const card1 = createCard();
      const card2 = createCard();
      flip(card1, 'A');
      flip(card2, 'A');
      expect(card1.classList.add).toHaveBeenCalledWith('matched');
      expect(card2.classList.add).toHaveBeenCalledWith('matched');
    });

    it('increments the pairs HUD counter by 1', () => {
      const before = hudPairs();
      flip(createCard(), 'A');
      flip(createCard(), 'A');
      expect(hudPairs()).toBe(before + 1);
    });

    it('records "match" with the new running pair count as its value', () => {
      const before = hudPairs();
      flip(createCard(), 'A');
      flip(createCard(), 'A');
      expect(collector.record).toHaveBeenCalledWith('match', before + 1);
    });

    it('records "card_flip" with a non-negative reaction time', () => {
      flip(createCard(), 'A');
      jest.advanceTimersByTime(200);
      flip(createCard(), 'A');

      const call = collector.record.mock.calls.find(([e]) => e === 'card_flip');
      expect(call).toBeDefined();
      expect(call[1]).toBeGreaterThanOrEqual(0);
    });

    it('resets first-card state so the next lone flip does not trigger a record call', () => {
      flip(createCard(), 'A');
      flip(createCard(), 'A'); // match → first = null

      collector.record.mockClear();

      flip(createCard(), 'B'); // first flip of a new pair — no event yet
      expect(collector.record).not.toHaveBeenCalled();
    });

    it('does not change the errors counter', () => {
      const before = hudErrors();
      flip(createCard(), 'A');
      flip(createCard(), 'A');
      expect(hudErrors()).toBe(before);
    });

    it('does not add the "wrong" class to either card', () => {
      const card1 = createCard();
      const card2 = createCard();
      flip(card1, 'A');
      flip(card2, 'A');
      expect(card1.classList.add.mock.calls.flat()).not.toContain('wrong');
      expect(card2.classList.add.mock.calls.flat()).not.toContain('wrong');
    });
  });

  // ── 5. Mismatch outcome ───────────────────────────────────────────────────

  describe('Mismatch outcome', () => {
    it('adds the "wrong" class to both mismatched cards', () => {
      const card1 = createCard();
      const card2 = createCard();
      flip(card1, 'A');
      flip(card2, 'B');
      expect(card1.classList.add).toHaveBeenCalledWith('wrong');
      expect(card2.classList.add).toHaveBeenCalledWith('wrong');
    });

    it('increments the errors HUD counter by 1', () => {
      const before = hudErrors();
      flip(createCard(), 'A');
      flip(createCard(), 'B');
      expect(hudErrors()).toBe(before + 1);
    });

    it('records "mismatch" with a null value', () => {
      flip(createCard(), 'A');
      flip(createCard(), 'B');
      expect(collector.record).toHaveBeenCalledWith('mismatch', null);
    });

    it('records "card_flip" with the reaction time on a mismatch', () => {
      flip(createCard(), 'A');
      jest.advanceTimersByTime(150);
      flip(createCard(), 'B');

      const call = collector.record.mock.calls.find(([e]) => e === 'card_flip');
      expect(call).toBeDefined();
      expect(call[1]).toBeGreaterThanOrEqual(0);
    });

    it('does not add the "matched" class to either card', () => {
      const card1 = createCard();
      const card2 = createCard();
      flip(card1, 'A');
      flip(card2, 'B');
      expect(card1.classList.add.mock.calls.flat()).not.toContain('matched');
      expect(card2.classList.add.mock.calls.flat()).not.toContain('matched');
    });

    it('does not change the pairs counter', () => {
      const before = hudPairs();
      flip(createCard(), 'A');
      flip(createCard(), 'B');
      expect(hudPairs()).toBe(before);
    });
  });

  // ── 6. Lock release after mismatch ────────────────────────────────────────
  //
  // gsapStub.to fires onComplete synchronously → registers setTimeout(300) in
  // the same tick → jest.advanceTimersByTime(300) fires it → lock=false,
  // first=null, classes removed from both cards.

  describe('Lock release after mismatch', () => {
    it('removes "flipped" and "wrong" from both cards after 300 ms', () => {
      const card1 = createCard();
      const card2 = createCard();
      flip(card1, 'A');
      flip(card2, 'B');
      jest.advanceTimersByTime(300);
      expect(card1.classList.remove).toHaveBeenCalledWith('flipped', 'wrong');
      expect(card2.classList.remove).toHaveBeenCalledWith('flipped', 'wrong');
    });

    it('allows flipping a new card once the 300 ms cleanup has fired', () => {
      flip(createCard(), 'A');
      flip(createCard(), 'B');
      jest.advanceTimersByTime(300);

      const card3 = createCard();
      flip(card3, 'C');
      expect(card3.classList.add).toHaveBeenCalledWith('flipped');
    });

    it('resets first-card state so the next lone flip is not treated as a second card', () => {
      flip(createCard(), 'A');
      flip(createCard(), 'B');
      jest.advanceTimersByTime(300);

      collector.record.mockClear();
      flip(createCard(), 'C'); // fresh first flip
      expect(collector.record).not.toHaveBeenCalled();
    });

    it('correctly records the next pair attempt after the lock releases', () => {
      flip(createCard(), 'A');
      flip(createCard(), 'B');
      jest.advanceTimersByTime(300);

      collector.record.mockClear();
      flip(createCard(), 'C');
      flip(createCard(), 'C'); // match following lock release

      expect(collector.record).toHaveBeenCalledWith('card_flip', expect.any(Number));
      expect(collector.record).toHaveBeenCalledWith('match',     expect.any(Number));
    });
  });

  // ── 7. submitScore ────────────────────────────────────────────────────────

  describe('submitScore', () => {
    it('calls collector.flush to send accumulated behavior data', () => {
      submitScore();
      expect(collector.flush).toHaveBeenCalled();
    });

    it('POSTs to the score endpoint', async () => {
      submitScore();
      await Promise.resolve();
      expect(fetchMock).toHaveBeenCalledWith(
        expect.stringContaining('memory_game_backend.php?action=score'),
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('sends a JSON body that contains points and completion_time fields', async () => {
      submitScore();
      await Promise.resolve();
      const body = JSON.parse(fetchMock.mock.calls[0][1].body);
      expect(body).toHaveProperty('points');
      expect(body).toHaveProperty('completion_time');
    });

    it('clamps points to a minimum of 0', async () => {
      submitScore();
      await Promise.resolve();
      const { points } = JSON.parse(fetchMock.mock.calls[0][1].body);
      expect(points).toBeGreaterThanOrEqual(0);
    });

    it('clamps points to a maximum of 10 000', async () => {
      submitScore();
      await Promise.resolve();
      const { points } = JSON.parse(fetchMock.mock.calls[0][1].body);
      expect(points).toBeLessThanOrEqual(10000);
    });

    it('enforces a minimum completion_time of 3 seconds', async () => {
      submitScore();
      await Promise.resolve();
      const { completion_time } = JSON.parse(fetchMock.mock.calls[0][1].body);
      expect(completion_time).toBeGreaterThanOrEqual(3);
    });

    it('calculates points with the formula (pairs×100) − (errors×15 + time×0.5)', async () => {
      // Read from HUD DOM — always in sync with the closure variables
      const pairs  = hudPairs();
      const errors = hudErrors();
      const time   = hudTime();
      const expected = Math.min(
        10000,
        Math.max(0, Math.round(pairs * 100 - (errors * 15 + time * 0.5)))
      );

      submitScore();
      await Promise.resolve();
      const { points } = JSON.parse(fetchMock.mock.calls[0][1].body);
      expect(points).toBe(expected);
    });
  });
});