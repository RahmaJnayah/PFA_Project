# -*- coding: utf-8 -*-
"""
test_02_sample_news.py
──────────────────────
Unit tests for ChildCareNewsExtractor.get_sample_news().

This method provides built-in fallback articles when all live RSS
sources fail. It must always return a valid, well-structured list.
"""


# ══════════════════════════════════════════════════════════════════════════════
#  get_sample_news
# ══════════════════════════════════════════════════════════════════════════════

class TestGetSampleNews:
    """get_sample_news() should always return at least 5 valid articles."""

    REQUIRED_KEYS = {"title", "content", "url", "publish_date", "source", "category"}

    def test_returns_list(self, extractor):
        assert isinstance(extractor.get_sample_news(), list)

    def test_minimum_five_items(self, extractor):
        assert len(extractor.get_sample_news()) >= 5

    def test_all_required_keys_present(self, extractor):
        for item in extractor.get_sample_news():
            missing = self.REQUIRED_KEYS - item.keys()
            assert not missing, f"Missing keys {missing} in article: {item['title']}"

    def test_titles_are_non_empty(self, extractor):
        for item in extractor.get_sample_news():
            assert item["title"].strip(), "title must not be blank"

    def test_content_minimum_length(self, extractor):
        for item in extractor.get_sample_news():
            assert len(item["content"]) >= 20, (
                f"Content too short in '{item['title']}'"
            )

    def test_urls_start_with_http(self, extractor):
        for item in extractor.get_sample_news():
            assert item["url"].startswith("http"), (
                f"Invalid URL in '{item['title']}': {item['url']}"
            )

    def test_publish_dates_are_strings(self, extractor):
        for item in extractor.get_sample_news():
            assert isinstance(item["publish_date"], str)

    def test_sources_are_non_empty(self, extractor):
        for item in extractor.get_sample_news():
            assert item["source"].strip(), "source must not be blank"

    def test_deterministic_output(self, extractor):
        """Two consecutive calls must return the same data."""
        first  = extractor.get_sample_news()
        second = extractor.get_sample_news()
        assert [a["title"] for a in first] == [a["title"] for a in second]