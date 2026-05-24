# -*- coding: utf-8 -*-
"""
test_01_html_and_dates.py
─────────────────────────
Unit tests for the two pure-Python helper methods of ChildCareNewsExtractor:
  · _clean_html()  — strips tags, unescapes HTML entities
  · _parse_date()  — parses RFC-2822 / ISO-8601 / garbage date strings
"""

import pytest
from datetime import datetime


# ══════════════════════════════════════════════════════════════════════════════
#  _clean_html
# ══════════════════════════════════════════════════════════════════════════════

class TestCleanHtml:
    """_clean_html should strip all HTML tags and unescape entities."""

    def test_strips_simple_tags(self, extractor):
        result = extractor._clean_html("<p>Hello <b>world</b></p>")
        assert "<" not in result
        assert "Hello" in result and "world" in result

    def test_unescapes_html_entities(self, extractor):
        result = extractor._clean_html("Children &amp; families")
        assert "&amp;" not in result
        assert "&" in result

    def test_empty_string(self, extractor):
        assert extractor._clean_html("") == ""

    def test_none_input_returns_empty(self, extractor):
        """None must not raise — returns empty string."""
        result = extractor._clean_html(None)
        assert result == ""

    def test_nested_tags_all_stripped(self, extractor):
        html = "<div><ul><li>Item 1</li><li>Item 2</li></ul></div>"
        result = extractor._clean_html(html)
        assert "Item 1" in result
        assert "Item 2" in result
        assert "<" not in result

    def test_script_tag_content_removed(self, extractor):
        result = extractor._clean_html("<script>alert('x')</script>Hello")
        assert "alert" not in result
        assert "Hello" in result

    def test_multiple_entity_types(self, extractor):
        result = extractor._clean_html("&lt;b&gt;bold&lt;/b&gt; &copy; 2024")
        assert "&lt;" not in result
        assert "&gt;" not in result


# ══════════════════════════════════════════════════════════════════════════════
#  _parse_date
# ══════════════════════════════════════════════════════════════════════════════

class TestParseDate:
    """_parse_date should handle RFC-2822, ISO-8601, and garbage input."""

    def test_rfc2822_date(self, extractor):
        result = extractor._parse_date("Mon, 01 Jan 2024 12:00:00 +0000")
        assert result == "2024-01-01"

    def test_rfc2822_different_timezone(self, extractor):
        result = extractor._parse_date("Fri, 15 Mar 2024 08:30:00 +0200")
        assert result.startswith("2024-03-")

    def test_iso_date_fallback(self, extractor):
        result = extractor._parse_date("2023-06-15T10:30:00Z")
        assert result.startswith("2023-06-15")

    def test_empty_string_returns_today(self, extractor):
        today = datetime.now().strftime("%Y-%m-%d")
        assert extractor._parse_date("") == today

    def test_none_returns_today(self, extractor):
        today = datetime.now().strftime("%Y-%m-%d")
        assert extractor._parse_date(None) == today

    def test_garbage_string_returns_non_empty_str(self, extractor):
        """
        Unrecognised strings fall back to date_str[:10].
        We only guarantee a non-empty string is returned.
        """
        result = extractor._parse_date("not-a-date-at-all!!")
        assert isinstance(result, str) and len(result) > 0

    def test_result_is_always_string(self, extractor):
        for val in ["", None, "bad", "Mon, 01 Jan 2024 00:00:00 +0000"]:
            assert isinstance(extractor._parse_date(val), str)