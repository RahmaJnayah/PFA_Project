# -*- coding: utf-8 -*-
"""
test_04_extract_news.py
───────────────────────
Unit tests for ChildCareNewsExtractor.extract_news().

Tests the high-level orchestration: iterating sources, deduplication,
num_posts limit, and fallback to sample data when all sources fail.
"""

import pytest
from unittest.mock import patch, MagicMock
from conftest import ChildCareNewsExtractor


def _article(title: str = "Test Article") -> dict:
    return {
        "title": title,
        "content": "Some meaningful content here for testing.",
        "url": "https://example.com",
        "publish_date": "2024-01-01",
        "source": "Test",
        "category": "",
    }


# ══════════════════════════════════════════════════════════════════════════════
#  Orchestration tests
# ══════════════════════════════════════════════════════════════════════════════

class TestExtractNews:

    def test_deduplicates_by_title(self, extractor):
        dup = _article("Same Title")
        with patch.object(ChildCareNewsExtractor, "_fetch_rss", return_value=[dup, dup]):
            result = extractor.extract_news(num_posts=10)
        assert [a["title"] for a in result].count("Same Title") == 1

    def test_deduplication_is_case_insensitive(self, extractor):
        a1 = _article("Child Health")
        a2 = _article("child health")          # same title, different case
        with patch.object(ChildCareNewsExtractor, "_fetch_rss", return_value=[a1, a2]):
            result = extractor.extract_news(num_posts=10)
        assert len(result) == 1

    def test_respects_num_posts_limit(self, extractor):
        articles = [_article(f"Article {i}") for i in range(30)]
        with patch.object(ChildCareNewsExtractor, "_fetch_rss", return_value=articles):
            result = extractor.extract_news(num_posts=5)
        assert len(result) <= 5

    def test_num_posts_zero_returns_empty(self, extractor):
        articles = [_article(f"Art {i}") for i in range(5)]
        with patch.object(ChildCareNewsExtractor, "_fetch_rss", return_value=articles):
            result = extractor.extract_news(num_posts=0)
        assert result == []

    def test_falls_back_to_sample_when_all_sources_fail(self, extractor):
        sample = [_article("Sample")]
        with patch.object(ChildCareNewsExtractor, "_fetch_rss", side_effect=Exception("fail")), \
             patch.object(ChildCareNewsExtractor, "get_sample_news", return_value=sample) as mock_sample:
            result = extractor.extract_news(num_posts=10)
        mock_sample.assert_called_once()
        assert result == sample

    def test_partial_source_failure_still_returns_articles(self, extractor):
        """If some sources fail but others succeed, articles are still returned."""
        good_article = _article("Good Article")

        call_count = {"n": 0}
        def side_effect(*args, **kwargs):
            call_count["n"] += 1
            if call_count["n"] % 2 == 0:
                raise Exception("source fail")
            return [good_article]

        with patch.object(ChildCareNewsExtractor, "_fetch_rss", side_effect=side_effect):
            result = extractor.extract_news(num_posts=10)
        assert len(result) >= 1

    def test_result_items_have_required_keys(self, extractor):
        articles = [_article(f"Art {i}") for i in range(3)]
        with patch.object(ChildCareNewsExtractor, "_fetch_rss", return_value=articles):
            result = extractor.extract_news(num_posts=10)
        required = {"title", "content", "url", "publish_date", "source", "category"}
        for item in result:
            assert required.issubset(item.keys())