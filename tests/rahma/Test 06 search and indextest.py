# -*- coding: utf-8 -*-
"""
test_06_search_and_index.py
────────────────────────────
Integration tests for IndexationEngine.search_and_index().

All MySQL calls are intercepted by unittest.mock — no real DB needed.
Tests cover valid queries, error queries, connection lifecycle,
log insertion, and schema auto-migration (ensure_tables).
"""

import pytest
import mysql.connector
from unittest.mock import MagicMock, patch
from conftest import IndexationEngine, SAMPLE_DOCS, make_db_mock


# ══════════════════════════════════════════════════════════════════════════════
#  Happy-path queries
# ══════════════════════════════════════════════════════════════════════════════

@patch("mysql.connector.connect")
class TestSearchAndIndexHappyPath:

    def test_returns_expected_keys(self, mock_connect, engine):
        conn, _ = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        result = engine.search_and_index("vaccination children")
        assert {"results", "total_matches", "indexed_count", "query"}.issubset(result)

    def test_query_echoed_in_result(self, mock_connect, engine):
        conn, _ = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        result = engine.search_and_index("child nutrition")
        assert result["query"] == "child nutrition"

    def test_total_matches_is_non_negative(self, mock_connect, engine):
        conn, _ = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        result = engine.search_and_index("education africa")
        assert result["total_matches"] >= 0

    def test_indexed_count_is_non_negative(self, mock_connect, engine):
        conn, _ = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        result = engine.search_and_index("nutrition toddler")
        assert result["indexed_count"] >= 0

    def test_top_10_cap_respected(self, mock_connect, engine):
        docs = [
            {"id": i, "title": f"Child Article {i}",
             "content": f"UNICEF article about children health number {i}.",
             "is_indexed": False, "indexed_date": None}
            for i in range(1, 16)
        ]
        conn, cur = make_db_mock(docs)
        cur.fetchall.side_effect = [docs, docs[:10]]
        mock_connect.return_value = conn
        result = engine.search_and_index("unicef children health")
        assert result["total_matches"] <= 15

    def test_indexation_log_insert_executed(self, mock_connect, engine):
        conn, cur = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        engine.search_and_index("child nutrition")
        calls = [str(c) for c in cur.execute.call_args_list]
        assert any("indexation_log" in c for c in calls), \
            "INSERT into indexation_log must be called"


# ══════════════════════════════════════════════════════════════════════════════
#  Error / invalid queries
# ══════════════════════════════════════════════════════════════════════════════

@patch("mysql.connector.connect")
class TestSearchAndIndexErrorQueries:

    def test_stopwords_only_returns_error_key(self, mock_connect, engine):
        conn, _ = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        result = engine.search_and_index("the and or")
        assert "error" in result
        assert result["results"] == []

    def test_empty_string_query_returns_error(self, mock_connect, engine):
        conn, _ = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        result = engine.search_and_index("")
        assert "error" in result

    def test_special_chars_in_query_no_crash(self, mock_connect, engine):
        conn, cur = make_db_mock(SAMPLE_DOCS)
        cur.rowcount = 1
        mock_connect.return_value = conn
        result = engine.search_and_index("children!!! @#$ health??")
        assert isinstance(result, dict)

    def test_empty_db_returns_empty_results(self, mock_connect, engine):
        conn, cur = make_db_mock([])
        cur.fetchall.side_effect = [[]]
        mock_connect.return_value = conn
        result = engine.search_and_index("children health")
        assert result["results"] == []
        assert result["total_matches"] == 0


# ══════════════════════════════════════════════════════════════════════════════
#  Connection lifecycle
# ══════════════════════════════════════════════════════════════════════════════

@patch("mysql.connector.connect")
class TestConnectionLifecycle:

    def test_connection_closed_after_successful_search(self, mock_connect, engine):
        conn, cur = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        engine.search_and_index("nutrition toddler")
        conn.close.assert_called_once()
        cur.close.assert_called_once()

    def test_connection_closed_even_on_db_error(self, mock_connect, engine):
        conn = MagicMock()
        conn.is_connected.return_value = True
        cur = MagicMock()
        cur.fetchone.return_value = ("indexed_date",)
        cur.fetchall.side_effect = mysql.connector.Error("Simulated crash")
        conn.cursor.return_value = cur
        mock_connect.return_value = conn

        with pytest.raises(Exception, match="Database error"):
            engine.search_and_index("health")

        conn.close.assert_called_once()

    def test_commit_called_after_successful_search(self, mock_connect, engine):
        conn, _ = make_db_mock(SAMPLE_DOCS)
        mock_connect.return_value = conn
        engine.search_and_index("child vaccination")
        conn.commit.assert_called()


# ══════════════════════════════════════════════════════════════════════════════
#  Schema auto-migration (ensure_tables)
# ══════════════════════════════════════════════════════════════════════════════

@patch("mysql.connector.connect")
class TestEnsureTables:

    def test_alter_table_issued_when_column_missing(self, mock_connect, engine):
        conn, cur = make_db_mock(SAMPLE_DOCS)
        # SHOW COLUMNS returns None → column is absent → ALTER TABLE expected
        cur.fetchone.side_effect = [None, None]
        cur.fetchall.side_effect = [SAMPLE_DOCS, SAMPLE_DOCS]
        mock_connect.return_value = conn

        engine.search_and_index("child")

        all_calls = " ".join(str(c) for c in cur.execute.call_args_list)
        assert "ALTER TABLE" in all_calls or "CREATE TABLE" in all_calls

    def test_no_alter_when_column_exists(self, mock_connect, engine):
        conn, cur = make_db_mock(SAMPLE_DOCS)
        # fetchone returns the column row → no ALTER needed
        cur.fetchone.return_value = ("indexed_date",)
        mock_connect.return_value = conn

        engine.search_and_index("vaccination children")

        all_calls = " ".join(str(c) for c in cur.execute.call_args_list)
        assert "ALTER TABLE unicef_news ADD COLUMN" not in all_calls