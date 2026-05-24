# -*- coding: utf-8 -*-
"""
test_03_rss_parsing.py
──────────────────────
Unit tests for ChildCareNewsExtractor._fetch_rss().

All HTTP calls are intercepted with unittest.mock — no real network needed.
Covers RSS 2.0, Atom, empty feeds, truncation rules, and HTTP errors.
"""

import pytest
from unittest.mock import MagicMock, patch
from conftest import (
    ChildCareNewsExtractor,
    FAKE_RSS_XML, FAKE_ATOM_XML, EMPTY_RSS_XML,
)


# ──────────────────────────────────────────────────────────────────────────────
# Helpers
# ──────────────────────────────────────────────────────────────────────────────

def _mock_response(content: bytes) -> MagicMock:
    resp = MagicMock()
    resp.content = content
    resp.raise_for_status = MagicMock()
    return resp


def _source(url: str = "https://example.com/feed") -> dict:
    return {"name": "Test", "url": url, "desc": "Test feed"}


REQUIRED_KEYS = {"title", "content", "url", "publish_date", "source", "category"}


# ══════════════════════════════════════════════════════════════════════════════
#  RSS 2.0 parsing
# ══════════════════════════════════════════════════════════════════════════════

@patch("extract_news.requests.get")
class TestFetchRSS:

    def test_parses_correct_number_of_rss_items(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_RSS_XML)
        articles = extractor._fetch_rss(_source())
        assert len(articles) == 2

    def test_rss_first_item_title(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_RSS_XML)
        articles = extractor._fetch_rss(_source())
        assert articles[0]["title"] == "Child Vaccination Tips"

    def test_rss_second_item_title(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_RSS_XML)
        articles = extractor._fetch_rss(_source())
        assert articles[1]["title"] == "Healthy Nutrition for Toddlers"

    def test_rss_articles_have_required_keys(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_RSS_XML)
        for art in extractor._fetch_rss(_source()):
            assert REQUIRED_KEYS.issubset(art.keys())

    def test_rss_category_captured(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_RSS_XML)
        articles = extractor._fetch_rss(_source())
        assert articles[0]["category"] == "Health"

    def test_rss_url_captured(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_RSS_XML)
        articles = extractor._fetch_rss(_source())
        assert articles[0]["url"] == "https://example.com/article1"


# ══════════════════════════════════════════════════════════════════════════════
#  Atom feed parsing
# ══════════════════════════════════════════════════════════════════════════════

@patch("extract_news.requests.get")
class TestFetchAtom:

    def test_parses_atom_entry_count(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_ATOM_XML)
        articles = extractor._fetch_rss(_source())
        assert len(articles) == 1

    def test_atom_entry_title(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_ATOM_XML)
        articles = extractor._fetch_rss(_source())
        assert "Outdoor Play" in articles[0]["title"]

    def test_atom_article_has_required_keys(self, mock_get, extractor):
        mock_get.return_value = _mock_response(FAKE_ATOM_XML)
        for art in extractor._fetch_rss(_source()):
            assert REQUIRED_KEYS.issubset(art.keys())


# ══════════════════════════════════════════════════════════════════════════════
#  Edge cases
# ══════════════════════════════════════════════════════════════════════════════

@patch("extract_news.requests.get")
class TestFetchRSSEdgeCases:

    def test_empty_feed_returns_empty_list(self, mock_get, extractor):
        mock_get.return_value = _mock_response(EMPTY_RSS_XML)
        assert extractor._fetch_rss(_source()) == []

    def test_title_capped_at_250_chars(self, mock_get, extractor):
        long_title = "A" * 300
        xml = (
            b"<?xml version='1.0'?><rss version='2.0'><channel>"
            b"<item><title>" + long_title.encode() +
            b"</title><description>Content long enough here to pass filter.</description>"
            b"<link>https://x.com</link></item></channel></rss>"
        )
        mock_get.return_value = _mock_response(xml)
        articles = extractor._fetch_rss(_source())
        if articles:
            assert len(articles[0]["title"]) <= 250

    def test_content_capped_at_1500_chars(self, mock_get, extractor):
        long_desc = "B " * 900   # 1800 chars
        xml = (
            b"<?xml version='1.0'?><rss version='2.0'><channel>"
            b"<item><title>Title</title><description>" + long_desc.encode() +
            b"</description><link>https://x.com</link></item></channel></rss>"
        )
        mock_get.return_value = _mock_response(xml)
        articles = extractor._fetch_rss(_source())
        if articles:
            assert len(articles[0]["content"]) <= 1500

    def test_item_with_short_content_filtered_out(self, mock_get, extractor):
        """Items whose content is < 20 chars after cleaning must be dropped."""
        xml = (
            b"<?xml version='1.0'?><rss version='2.0'><channel>"
            b"<item><title>Title</title><description>Short</description>"
            b"<link>https://x.com</link></item></channel></rss>"
        )
        mock_get.return_value = _mock_response(xml)
        articles = extractor._fetch_rss(_source())
        assert articles == []

    def test_http_error_propagates(self, mock_get, extractor):
        mock_get.return_value = _mock_response(b"")
        mock_get.return_value.raise_for_status.side_effect = Exception("404 Not Found")
        with pytest.raises(Exception, match="404"):
            extractor._fetch_rss(_source())