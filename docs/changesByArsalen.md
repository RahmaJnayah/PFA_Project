Changes Report - Behavioral Analysis Integration
Date: 2026-04-11

1) Overview
This work added a non-ML behavioral analysis pipeline for game sessions, stored the results in the database, and integrated the results into both the admin dashboard and the parent Sentiment Hub UI. The system now computes session-level emotional signals, aggregates them for a period, and presents multi-child trends with charts, insights, and history tables.

2) Database and Storage
2.1 New table
- child_behavior_analysis
	Purpose: store per-period analysis (scores, dominant state, confidence, details, weights).
	Added in database/schema.sql.

2.2 Behavior data sources already in use
- game_behaviors (signals, raw_signals) and game_events (event stream)
	The analysis engine reads both; raw_signals is primary, signals is fallback.

3) Analysis Engine (Non-ML)
3.1 Core engine
- Implemented BehaviorAnalyzer with:
	- Age-calibrated baselines (latency, error rate)
	- Event rules (focus, frustration, boredom, joy)
	- Temporal decay every 30 seconds
	- Per-game weighting
	- Data-quality weighting (raw_signals vs summary-only)
	- Per-session analysis + period aggregation
	- History merge (recent analyses influence current result)

3.2 Configuration
- Added analysis_config.php with:
	- Baselines per age group
	- Per-game weights
	- Rule thresholds and decay settings
	- Data-quality weights

3.3 Weights documentation
- Added game_weight_for_analysis.md explaining why each game weight was chosen and how to override.

3.4 Fixes and tuning
- Added Memory Match fallback frustration logic when pair metadata is missing.
- Updated storeAnalysis to upsert (update existing period instead of failing on duplicate).

4) Admin Dashboard Integration
4.1 New admin model
- AdminModuleAnalysisModel for recent and chronological analysis reads.

4.2 Dashboard UI
- Added analysis controls and history list:
	- Run analysis per child
	- Run analysis for all children
	- Chronological analysis table
	- Flash messages for actions

4.3 Admin routing and controller
- Added actions to run analysis from admin dashboard.
- Added child list for selection.

5) Parent Sentiment Hub Integration (UNICEF UI)
5.1 Live data API
- Added parent_analysis_api.php:
	- Auth guard for parent session
	- Custom date range inputs
	- Returns per-session analysis data for all children

5.2 Dashboard dynamic data
- Replaced static curves with live multi-child line chart.
- Added custom date range selector.
- Added insights panel and analysis history table.
- Calculated balance scores and trend signals from live data.

5.3 Mood Map and Deep Insights
- Mood Map now uses live session data for last 7 days.
- Weekly emotion chart uses live aggregates.
- Deep Insights story, chips, trend metrics, and growth chart now use live data.

6) Files Added
- ai/analysis/analysis_config.php
- ai/analysis/game_weight_for_analysis.md
- ai/analysis/run_daily_analysis.php
- ai/analysis/test_analysis.php
- ai/analysis/sentiment_analysis_logic.md
- api/parent_analysis_api.php
- models/AdminModuleAnalysisModel.php

7) Files Modified
- ai/analysis/BehaviorAnalyzer.php
- database/schema.sql
- controllers/AdminModuleController.php
- admin.php
- models/AdminModuleChildModel.php
- views/admin/admin-dashboard.php
- views/parent/dashboard/unicef_indexation/njareb/index.php
- views/parent/dashboard/unicef_indexation/njareb/app.js
- views/parent/dashboard/unicef_indexation/njareb/style.css

8) Behavior and UX Notes
- Analysis updates overwrite the existing period row (no new row count per rerun).
- Memory Match frustration is now computed even without pair metadata (reduced severity).
- Parent Sentiment Hub now requires parent session to view the UNICEF dashboard.

9) Suggested Next Steps
- Add pair identifiers in Memory Match raw_signals to enable precise error matching.
- Add caching for parent analysis API if session volume grows.
- Add pagination on analysis history table if needed.
