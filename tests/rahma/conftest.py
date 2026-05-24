# -*- coding: utf-8 -*-
"""
conftest.py — Shared fixtures for the entire UNICEF News test suite.

Loaded automatically by pytest before any test file runs.
All test files import their fixtures from here via pytest injection,
or import the shared constants/helpers directly.

Run the full suite:
    pip install pytest pytest-mock mysql-connector-python beautifulsoup4 requests
    pytest unicef_tests/ -v --tb=short
"""

import sys
import importlib
import pathlib
import pytest
from unittest.mock import MagicMock

# ──────────────────────────────────────────────────────────────────────────────
# Locate the project source files
# ──────────────────────────────────────────────────────────────────────────────
PROJECT_ROOT = pathlib.Path(__file__).parent.parent.parent
PYTHON_MODULES_PATH = PROJECT_ROOT / "views" / "parent" / "dashboard" / "unicef_indexation" / "python"


def _import_module_no_main(path, module_name: str):
    """Import a .py file without running its __main__ block."""
    spec = importlib.util.spec_from_file_location(module_name, path)
    mod = importlib.util.module_from_spec(spec)
    mod.__name__ = module_name          # prevents __main__ guard from firing
    sys.modules[module_name] = mod
    spec.loader.exec_module(mod)
    return mod


# ──────────────────────────────────────────────────────────────────────────────
# Import both production modules once (shared across all test files)
# ──────────────────────────────────────────────────────────────────────────────
extract_mod = _import_module_no_main(
    PYTHON_MODULES_PATH / "extract_news.py", "extract_news"
)
index_mod = _import_module_no_main(
    PYTHON_MODULES_PATH / "indexation_engine.py", "indexation_engine"
)

ChildCareNewsExtractor = extract_mod.ChildCareNewsExtractor
IndexationEngine       = index_mod.IndexationEngine


# ──────────────────────────────────────────────────────────────────────────────
# Shared fake XML payloads (used by multiple test files)
# ──────────────────────────────────────────────────────────────────────────────
FAKE_RSS_XML = b"""<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Test Feed</title>
    <item>
      <title>Child Vaccination Tips</title>
      <description>Vaccines protect children from dangerous diseases early in life.</description>
      <link>https://example.com/article1</link>
      <pubDate>Mon, 01 Jan 2024 10:00:00 +0000</pubDate>
      <category>Health</category>
    </item>
    <item>
      <title>Healthy Nutrition for Toddlers</title>
      <description>A balanced diet rich in vegetables and protein supports brain growth.</description>
      <link>https://example.com/article2</link>
      <pubDate>Tue, 02 Jan 2024 10:00:00 +0000</pubDate>
    </item>
  </channel>
</rss>"""

FAKE_ATOM_XML = b"""<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <entry>
    <title>Outdoor Play Benefits</title>
    <summary>Playing outdoors daily improves children's creativity and focus.</summary>
    <link href="https://example.com/atom1"/>
    <updated>2024-03-15T09:00:00Z</updated>
  </entry>
</feed>"""

EMPTY_RSS_XML = b"""<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>Empty Feed</title></channel></rss>"""

# ──────────────────────────────────────────────────────────────────────────────
# Shared sample DB documents
# ──────────────────────────────────────────────────────────────────────────────
SAMPLE_DOCS = [
    {
        "id": 1,
        "title": "Child Vaccination Campaign",
        "content": "UNICEF launches vaccination drive for children across rural areas.",
        "is_indexed": False,
        "indexed_date": None,
    },
    {
        "id": 2,
        "title": "Nutrition for Toddlers",
        "content": "Balanced diet rich in proteins helps toddler brain development.",
        "is_indexed": False,
        "indexed_date": None,
    },
    {
        "id": 3,
        "title": "Education Programs in Africa",
        "content": "New education initiatives bring schools to remote communities.",
        "is_indexed": False,
        "indexed_date": None,
    },
]


# ──────────────────────────────────────────────────────────────────────────────
# Helper: build a complete DB mock chain from a list of documents
# ──────────────────────────────────────────────────────────────────────────────
def make_db_mock(documents):
    """
    Return (conn_mock, cursor_mock) that mimics mysql.connector.connect().

    Behaviour:
      fetchone  → ("indexed_date",)  — SHOW COLUMNS finds the column
      fetchall  → [documents, documents]  — SELECT list + SELECT details
      rowcount  → 1  — UPDATE affected one row
    """
    cursor_mock = MagicMock()
    cursor_mock.fetchone.return_value = ("indexed_date",)
    cursor_mock.fetchall.side_effect = [list(documents), list(documents)]
    cursor_mock.rowcount = 1

    conn_mock = MagicMock()
    conn_mock.cursor.return_value = cursor_mock
    conn_mock.is_connected.return_value = True
    return conn_mock, cursor_mock


# ──────────────────────────────────────────────────────────────────────────────
# pytest fixtures (available in every test file automatically)
# ──────────────────────────────────────────────────────────────────────────────
@pytest.fixture
def extractor():
    """Fresh ChildCareNewsExtractor for each test."""
    return ChildCareNewsExtractor()


@pytest.fixture
def engine():
    """Fresh IndexationEngine for each test."""
    return IndexationEngine()


@pytest.fixture
def fake_rss_response():
    """MagicMock HTTP response wrapping FAKE_RSS_XML."""
    resp = MagicMock()
    resp.content = FAKE_RSS_XML
    resp.raise_for_status = MagicMock()
    return resp


@pytest.fixture
def sample_article():
    """A single minimal valid article dict."""
    return {
        "title": "Vaccination Drive",
        "content": "Children receive vaccines to stay healthy and protected.",
        "url": "https://example.com",
        "publish_date": "2024-01-01",
        "source": "CDC",
        "category": "Health",
    }