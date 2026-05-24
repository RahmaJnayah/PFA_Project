/**
 * @jest-environment jsdom
 *
 * BehaviorCollector.test.js — complete unit test suite
 *
 * WHY @jest-environment jsdom
 * ────────────────────────────────────────────────────────────────────────────
 * BehaviorCollector uses navigator.sendBeacon and Blob — both browser APIs.
 * jsdom provides them so we can stub and assert without a real browser.
 * Without this docblock Jest defaults to the "node" environment and every
 * reference to `navigator` or `document` throws ReferenceError.
 *
 * WHY loadScript INSTEAD OF readFileSync + eval
 * ────────────────────────────────────────────────────────────────────────────
 * testHelpers.loadScript resolves paths relative to its own __dirname using
 * the project-root-relative convention `'PRO-PFE-YOPY/yopy-platform/...'`.
 * Attempting to resolve the same path with a hand-written `path.resolve`
 * inside this file produces a different absolute path and throws ENOENT.
 * loadScript also caches scripts so they are only evaluated once per worker.
 *
 * Coverage map
 * ────────────────────────────────────────────────────────────────────────────
 * 1. Constructor            — defaults, custom options, initial state
 * 2. setSessionId           — numeric, non-numeric, zero, large, overwrite
 * 3. record() — routing     — valid mapping, unknown key, invalid signal, all VALID_SIGNALS
 * 4. record() — reaction    — _lastReact tracking, pace_change threshold edge cases
 * 5. record() — idle timer  — abandon fired, duration stored, timer reset, fires once
 * 6. record() — auto-flush  — below threshold, at threshold, queue cleared, continues
 * 7. _aggregate()           — latency_avg, error_rate, session_duration, event_count,
 *                             null reactions excluded, division-by-zero guard
 * 8. flush()                — empty early return, sendBeacon payload shape,
 *                             fetch fallback, state cleared, consecutive flush idempotency
 */

'use strict';

const { loadScript } = require('./testHelpers');

// ── Load BehaviorCollector into the global scope ──────────────────────────────
// loadScript evals the file inside a vm sandbox seeded from global, then copies
// top-level declarations back. After this call `BehaviorCollector` is available
// as a global just as it would be in a browser script tag.
jest.useFakeTimers();
if (!global.navigator) global.navigator = {};
const fetchMock = jest.fn(() => Promise.resolve({}));
global.fetch = fetchMock;

// Load BehaviorCollector into the global scope after seeding globals above.
loadScript('PRO-PFE-YOPY/yopy-platform/public/js/games/BehaviorCollector.js');

// ── Shared fixtures ───────────────────────────────────────────────────────────

const SIGNAL_MAP = {
  card_flip: 'reaction',
  match:     'success',
  mismatch:  'error',
  hint_used: 'hint',
  game_over: 'timeout',
};

/** Returns a fresh collector with a low flushThreshold for threshold tests. */
function makeCollector(overrides = {}) {
  return new BehaviorCollector('memory_game', SIGNAL_MAP, {
    flushThreshold: 10,
    idleTimeout:    2000,
    ...overrides,
  });
}

// ── Suite ─────────────────────────────────────────────────────────────────────

describe('BehaviorCollector', () => {
  let collector;
  let beaconMock;
  let consoleSpy;

  beforeEach(() => {
    jest.useFakeTimers();

    // Silence console.log calls inside the class
    consoleSpy = jest.spyOn(console, 'log').mockImplementation(() => {});

    // Stub navigator.sendBeacon — jsdom provides navigator so we can mutate it
    beaconMock = jest.fn().mockReturnValue(true);
    Object.defineProperty(global.navigator, 'sendBeacon', {
      value:        beaconMock,
      writable:     true,
      configurable: true,
    });

    // Stub fetch as the no-sendBeacon fallback
      fetchMock.mockResolvedValue({});
    global.fetch = fetchMock;

    // Fresh instance per test — no shared mutable state between tests
    collector = makeCollector();
  });

  afterEach(() => {
    jest.runAllTimers();
    jest.useRealTimers();
    jest.clearAllMocks();
    consoleSpy.mockRestore();
  });

  // ── 1. Constructor ────────────────────────────────────────────────────────

  describe('constructor', () => {
    it('stores the gameId', () => {
      const c = new BehaviorCollector('quiz_game', SIGNAL_MAP);
      expect(c.gameId).toBe('quiz_game');
    });

    it('stores the signalMap reference', () => {
      const c = new BehaviorCollector('quiz_game', SIGNAL_MAP);
      expect(c.signalMap).toBe(SIGNAL_MAP);
    });

    it('uses default flushThreshold of 60', () => {
      const c = new BehaviorCollector('game', SIGNAL_MAP);
      expect(c.flushThreshold).toBe(60);
    });

    it('uses default idleTimeout of 8000 ms', () => {
      const c = new BehaviorCollector('game', SIGNAL_MAP);
      expect(c.idleTimeout).toBe(8000);
    });

    it('accepts a custom flushThreshold', () => {
      const c = new BehaviorCollector('game', SIGNAL_MAP, { flushThreshold: 5 });
      expect(c.flushThreshold).toBe(5);
    });

    it('accepts a custom idleTimeout', () => {
      const c = new BehaviorCollector('game', SIGNAL_MAP, { idleTimeout: 500 });
      expect(c.idleTimeout).toBe(500);
    });

    it('initialises sessionId as null', () => {
      expect(collector.sessionId).toBeNull();
    });

    it('initialises the event queue as empty', () => {
      expect(collector._events).toHaveLength(0);
    });

    it('initialises _lastReact as null', () => {
      expect(collector._lastReact).toBeNull();
    });
  });

  // ── 2. setSessionId ───────────────────────────────────────────────────────

  describe('setSessionId', () => {
    it('parses a valid numeric string and stores the integer', () => {
      collector.setSessionId('42');
      expect(collector.sessionId).toBe(42);
    });

    it('handles a large numeric id correctly', () => {
      collector.setSessionId('99999');
      expect(collector.sessionId).toBe(99999);
    });

    it('stores 0 when given "0"', () => {
      collector.setSessionId('0');
      expect(collector.sessionId).toBe(0);
    });

    it('stores 0 when given a non-numeric string', () => {
      collector.setSessionId('abc');
      expect(collector.sessionId).toBe(0);
    });

    it('stores 0 when given an empty string', () => {
      collector.setSessionId('');
      expect(collector.sessionId).toBe(0);
    });

    it('overwrites a previously stored sessionId', () => {
      collector.setSessionId('10');
      collector.setSessionId('20');
      expect(collector.sessionId).toBe(20);
    });
  });

  // ── 3. record() — signal routing ──────────────────────────────────────────

  describe('record() — signal routing', () => {
    it('adds one event to the queue for a valid mapped game event', () => {
      collector.record('match', 1);
      expect(collector._events).toHaveLength(1);
    });

    it('stores the mapped signal name on the pushed event', () => {
      collector.record('match', 1);
      expect(collector._events[0].signal).toBe('success');
    });

    it('stores the provided value on the pushed event', () => {
      collector.record('match', 7);
      expect(collector._events[0].value).toBe(7);
    });

    it('defaults value to null when none is supplied', () => {
      collector.record('mismatch');
      expect(collector._events[0].value).toBeNull();
    });

    it('silently ignores a game event not present in the signalMap', () => {
      collector.record('unknown_event', 1);
      expect(collector._events).toHaveLength(0);
    });

    it('silently ignores an event whose mapped signal is not in VALID_SIGNALS', () => {
      const badMap = { my_event: 'not_a_valid_signal' };
      const c = new BehaviorCollector('game', badMap);
      c.record('my_event', 1);
      expect(c._events).toHaveLength(0);
    });

    it('accepts every signal in the VALID_SIGNALS set without filtering', () => {
      const fullMap = {
        e1: 'reaction', e2: 'error',   e3: 'success', e4: 'abandon',
        e5: 'pace_change', e6: 'retry', e7: 'hint',   e8: 'timeout',
      };
      const c = new BehaviorCollector('game', fullMap, { flushThreshold: 100 });
      Object.keys(fullMap).forEach(e => c.record(e, 1));
      const storedSignals = c._events.map(ev => ev.signal);
      Object.values(fullMap).forEach(sig => expect(storedSignals).toContain(sig));
    });
  });

  // ── 4. record() — reaction tracking and pace_change ───────────────────────

  describe('record() — reaction tracking', () => {
    it('sets _lastReact on the first reaction event', () => {
      collector.record('card_flip', 300);
      expect(collector._lastReact).toBe(300);
    });

    it('updates _lastReact on every subsequent reaction', () => {
      collector.record('card_flip', 300);
      collector.record('card_flip', 500);
      expect(collector._lastReact).toBe(500);
    });

    it('does not push a pace_change event on the very first reaction', () => {
      collector.record('card_flip', 300);
      const signals = collector._events.map(e => e.signal);
      expect(signals).not.toContain('pace_change');
    });

    it('pushes a pace_change event when the positive delta exceeds 200 ms', () => {
      collector.record('card_flip', 100);
      collector.record('card_flip', 350); // delta = 250 > 200
      const signals = collector._events.map(e => e.signal);
      expect(signals).toContain('pace_change');
    });

    it('stores the exact positive delta on the pace_change event', () => {
      collector.record('card_flip', 100);
      collector.record('card_flip', 350); // delta = 250
      const paceEvent = collector._events.find(e => e.signal === 'pace_change');
      expect(paceEvent.value).toBe(250);
    });

    it('pushes a pace_change event when the absolute negative delta exceeds 200 ms', () => {
      collector.record('card_flip', 500);
      collector.record('card_flip', 200); // delta = −300, |−300| > 200
      const signals = collector._events.map(e => e.signal);
      expect(signals).toContain('pace_change');
    });

    it('stores the signed negative delta on the pace_change event', () => {
      collector.record('card_flip', 500);
      collector.record('card_flip', 200); // delta = −300
      const paceEvent = collector._events.find(e => e.signal === 'pace_change');
      expect(paceEvent.value).toBe(-300);
    });

    it('does NOT push pace_change when the delta is exactly 200 ms (boundary)', () => {
      collector.record('card_flip', 100);
      collector.record('card_flip', 300); // delta = 200 — NOT > 200
      const signals = collector._events.map(e => e.signal);
      expect(signals).not.toContain('pace_change');
    });

    it('does NOT push pace_change when the delta is below 200 ms', () => {
      collector.record('card_flip', 100);
      collector.record('card_flip', 250); // delta = 150 < 200
      const signals = collector._events.map(e => e.signal);
      expect(signals).not.toContain('pace_change');
    });

    it('does not set _lastReact for non-reaction events', () => {
      collector.record('mismatch', null); // maps to 'error'
      expect(collector._lastReact).toBeNull();
    });
  });

  // ── 5. record() — idle timer ──────────────────────────────────────────────

  describe('record() — idle timer', () => {
    it('pushes an "abandon" event after idleTimeout ms of inactivity', () => {
      collector.record('match', 1);
      jest.advanceTimersByTime(2000); // idleTimeout = 2000
      const signals = collector._events.map(e => e.signal);
      expect(signals).toContain('abandon');
    });

    it('stores the idle duration in seconds on the abandon event', () => {
      collector.record('match', 1);
      jest.advanceTimersByTime(2000);
      const abandonEvent = collector._events.find(e => e.signal === 'abandon');
      expect(abandonEvent.value).toBeCloseTo(2, 0);
    });

    it('resets the idle timer on each subsequent record() call', () => {
      collector.record('match', 1);
      jest.advanceTimersByTime(1500); // almost at 2000 ms
      collector.record('match', 2);  // resets the timer
      jest.advanceTimersByTime(1500); // only 1500 ms have passed since the reset
      const signals = collector._events.map(e => e.signal);
      expect(signals).not.toContain('abandon');
    });

    it('fires abandon exactly once when idleTimeout elapses after the last record', () => {
      collector.record('match', 1);
      jest.advanceTimersByTime(1500);
      collector.record('match', 2); // reset
      jest.advanceTimersByTime(2000);
      const abandonEvents = collector._events.filter(e => e.signal === 'abandon');
      expect(abandonEvents).toHaveLength(1);
    });
  });

  // ── 6. record() — auto-flush at flushThreshold ────────────────────────────

  describe('record() — auto-flush', () => {
    it('does not flush before the threshold is reached', () => {
      const c = makeCollector({ flushThreshold: 3 });
      c.record('match', 1);
      c.record('match', 2); // 2 events — threshold is 3
      expect(beaconMock).not.toHaveBeenCalled();
    });

    it('triggers flush when event count reaches flushThreshold', () => {
      const c = makeCollector({ flushThreshold: 3 });
      c.record('match', 1);
      c.record('match', 2);
      c.record('match', 3); // hits threshold
      expect(beaconMock).toHaveBeenCalledTimes(1);
    });

    it('clears the event queue after an auto-flush', () => {
      const c = makeCollector({ flushThreshold: 3 });
      c.record('match', 1);
      c.record('match', 2);
      c.record('match', 3);
      expect(c._events).toHaveLength(0);
    });

    it('continues accepting new events after an auto-flush', () => {
      const c = makeCollector({ flushThreshold: 3 });
      c.record('match', 1);
      c.record('match', 2);
      c.record('match', 3); // auto-flush
      c.record('match', 4); // new event post-flush
      expect(c._events).toHaveLength(1);
    });
  });

  // ── 7. _aggregate() ───────────────────────────────────────────────────────

  describe('_aggregate()', () => {
    it('calculates the mean of all reaction values as latency_avg', () => {
      collector.record('card_flip', 200);
      collector.record('card_flip', 400);
      const agg = collector._aggregate();
      expect(agg.latency_avg).toBeCloseTo(300);
    });

    it('returns null for latency_avg when there are no reaction events', () => {
      collector.record('mismatch', null);
      const agg = collector._aggregate();
      expect(agg.latency_avg).toBeNull();
    });

    it('excludes reaction events with a null value from latency_avg', () => {
      collector._events.push({ signal: 'reaction', value: null, ts: Date.now() });
      collector._events.push({ signal: 'reaction', value: 400,  ts: Date.now() });
      const agg = collector._aggregate();
      expect(agg.latency_avg).toBeCloseTo(400);
    });

    it('calculates error_rate as (error count) / (total events)', () => {
      collector.record('match',    1); // success
      collector.record('mismatch');    // error
      collector.record('mismatch');    // error  →  2 errors / 3 total
      const agg = collector._aggregate();
      expect(agg.error_rate).toBeCloseTo(2 / 3);
    });

    it('returns error_rate of 0 when there are no error events', () => {
      collector.record('match', 1);
      collector.record('match', 2);
      const agg = collector._aggregate();
      expect(agg.error_rate).toBe(0);
    });

    it('returns a positive session_duration after time has elapsed', () => {
      jest.advanceTimersByTime(3000);
      collector.record('match', 1);
      const agg = collector._aggregate();
      expect(agg.session_duration).toBeGreaterThan(0);
    });

    it('reports event_count equal to the number of stored events', () => {
      collector.record('match',    1);
      collector.record('mismatch');
      const agg = collector._aggregate();
      expect(agg.event_count).toBe(collector._events.length);
    });

    it('uses 1 as the denominator for error_rate when the event list is empty (no division by zero)', () => {
      const agg = collector._aggregate(); // _events is empty
      expect(agg.error_rate).toBe(0); // 0 errors / 1 = 0
    });
  });

  // ── 8. flush() ────────────────────────────────────────────────────────────

  describe('flush()', () => {

    describe('early return — empty queue', () => {
      it('does not call sendBeacon when there are no events to flush', async () => {
        await collector.flush();
        expect(beaconMock).not.toHaveBeenCalled();
      });

      it('does not call fetch when there are no events to flush', async () => {
        await collector.flush();
        expect(fetchMock).not.toHaveBeenCalled();
      });
    });

    describe('sendBeacon path — navigator.sendBeacon is available', () => {
      beforeEach(() => {
        collector.record('match', 1);
      });

      it('calls navigator.sendBeacon with the behavior API URL', async () => {
        await collector.flush();
        expect(beaconMock).toHaveBeenCalledWith(
          expect.stringContaining('behavior_api.php'),
          expect.anything()
        );
      });

      it('sends game_id in the payload', async () => {
        await collector.flush();
        const raw  = beaconMock.mock.calls[0][1];
        const text = raw instanceof Blob ? await raw.text() : raw;
        expect(JSON.parse(text).game_id).toBe('memory_game');
      });

      it('sends session_id in the payload after setSessionId is called', async () => {
        collector.setSessionId('77');
        await collector.flush();
        const raw  = beaconMock.mock.calls[0][1];
        const text = raw instanceof Blob ? await raw.text() : raw;
        expect(JSON.parse(text).session_id).toBe(77);
      });

      it('includes a signals object with latency_avg, error_rate, session_duration, event_count', async () => {
        await collector.flush();
        const raw  = beaconMock.mock.calls[0][1];
        const text = raw instanceof Blob ? await raw.text() : raw;
        const { signals } = JSON.parse(text);
        expect(signals).toHaveProperty('latency_avg');
        expect(signals).toHaveProperty('error_rate');
        expect(signals).toHaveProperty('session_duration');
        expect(signals).toHaveProperty('event_count');
      });

      it('includes all queued events in the payload', async () => {
        collector.record('mismatch'); // second event
        await collector.flush();
        const raw  = beaconMock.mock.calls[0][1];
        const text = raw instanceof Blob ? await raw.text() : raw;
        expect(JSON.parse(text).events).toHaveLength(2);
      });

      it('clears the event queue after flush', async () => {
        await collector.flush();
        expect(collector._events).toHaveLength(0);
      });

      it('resets _lastReact to null after flush', async () => {
        collector.record('card_flip', 300);
        await collector.flush();
        expect(collector._lastReact).toBeNull();
      });

      it('does not call fetch when sendBeacon is available', async () => {
        await collector.flush();
        expect(fetchMock).not.toHaveBeenCalled();
      });
    });

    describe('fetch fallback path — navigator.sendBeacon is not available', () => {
      beforeEach(() => {
        // Remove sendBeacon to trigger the fetch fallback branch
        Object.defineProperty(global.navigator, 'sendBeacon', {
          value: undefined, writable: true, configurable: true,
        });
        collector.record('match', 1);
      });

      it('calls fetch when sendBeacon is unavailable', async () => {
        await collector.flush();
        expect(fetchMock).toHaveBeenCalledTimes(1);
      });

      it('POSTs to the behavior API URL', async () => {
        await collector.flush();
        expect(fetchMock).toHaveBeenCalledWith(
          expect.stringContaining('behavior_api.php'),
          expect.objectContaining({ method: 'POST' })
        );
      });

      it('sets Content-Type to application/json', async () => {
        await collector.flush();
        const opts = fetchMock.mock.calls[0][1];
        expect(opts.headers).toMatchObject({ 'Content-Type': 'application/json' });
      });

      it('sends a parseable JSON body containing game_id', async () => {
        await collector.flush();
        const body = JSON.parse(fetchMock.mock.calls[0][1].body);
        expect(body.game_id).toBe('memory_game');
      });

      it('clears the event queue even when the fetch path is used', async () => {
        await collector.flush();
        expect(collector._events).toHaveLength(0);
      });

      it('does not throw when fetch rejects with a network error', async () => {
        fetchMock.mockRejectedValueOnce(new Error('network failure'));
        await expect(collector.flush()).resolves.not.toThrow();
      });
    });

    describe('consecutive flushes', () => {
      it('does not send a second beacon if flush is called again immediately after', async () => {
        collector.record('match', 1);
        await collector.flush();      // clears queue
        beaconMock.mockClear();
        await collector.flush();      // queue is empty — should be a no-op
        expect(beaconMock).not.toHaveBeenCalled();
      });

      it('sends a new beacon when new events are recorded after a flush', async () => {
        collector.record('match', 1);
        await collector.flush();
        collector.record('match', 2); // new event post-flush
        await collector.flush();
        expect(beaconMock).toHaveBeenCalledTimes(1);
      });
    });
  });
});