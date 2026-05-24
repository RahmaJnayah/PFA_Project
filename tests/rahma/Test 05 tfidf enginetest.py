# -*- coding: utf-8 -*-
"""
test_05_tfidf_engine.py
───────────────────────
Unit tests for the pure-Python NLP methods of IndexationEngine:
  · preprocess_text()  — tokenise, lowercase, remove stopwords/punctuation
  · compute_tf()       — Term Frequency
  · compute_idf()      — Inverse Document Frequency
  · TF-IDF title boost logic
"""

import math
import pytest
from collections import Counter


# ══════════════════════════════════════════════════════════════════════════════
#  preprocess_text
# ══════════════════════════════════════════════════════════════════════════════

class TestPreprocessText:

    def test_lowercases_text(self, engine):
        tokens = engine.preprocess_text("HELLO WORLD CHILDREN")
        assert all(t == t.lower() for t in tokens)

    def test_removes_punctuation(self, engine):
        tokens = engine.preprocess_text("children's, health!")
        for t in tokens:
            assert t.isalpha(), f"Non-alpha token found: '{t}'"

    def test_removes_common_stopwords(self, engine):
        tokens = engine.preprocess_text("the children are healthy and active")
        for sw in {"the", "are", "and"}:
            assert sw not in tokens

    def test_removes_short_tokens(self, engine):
        tokens = engine.preprocess_text("a be go run fast")
        assert all(len(t) > 2 for t in tokens)

    def test_empty_string_returns_empty_list(self, engine):
        assert engine.preprocess_text("") == []

    def test_only_stopwords_returns_empty(self, engine):
        assert engine.preprocess_text("the and or but") == []

    def test_returns_list(self, engine):
        assert isinstance(engine.preprocess_text("child health vaccine"), list)

    def test_unicode_input_does_not_raise(self, engine):
        result = engine.preprocess_text("enfants santé développement")
        assert isinstance(result, list)

    def test_numbers_removed(self, engine):
        tokens = engine.preprocess_text("children 123 health 456")
        for t in tokens:
            assert not any(c.isdigit() for c in t)

    def test_repeated_word_kept_multiple_times(self, engine):
        tokens = engine.preprocess_text("child child child health")
        assert tokens.count("child") == 3


# ══════════════════════════════════════════════════════════════════════════════
#  compute_tf
# ══════════════════════════════════════════════════════════════════════════════

class TestComputeTF:

    def test_sum_of_tf_equals_one(self, engine):
        tokens = ["child", "child", "health", "nutrition"]
        tf = engine.compute_tf(tokens)
        assert abs(sum(tf.values()) - 1.0) < 1e-9

    def test_higher_frequency_has_higher_tf(self, engine):
        tokens = ["child"] * 3 + ["health"]
        tf = engine.compute_tf(tokens)
        assert tf["child"] > tf["health"]

    def test_single_token_tf_is_one(self, engine):
        tf = engine.compute_tf(["child"])
        assert tf["child"] == pytest.approx(1.0)

    def test_all_values_between_zero_and_one(self, engine):
        tokens = ["child", "health", "vaccine", "child"]
        tf = engine.compute_tf(tokens)
        for v in tf.values():
            assert 0.0 < v <= 1.0

    def test_empty_tokens_returns_empty_counter(self, engine):
        assert engine.compute_tf([]) == Counter()

    def test_returns_counter(self, engine):
        result = engine.compute_tf(["child", "health"])
        assert isinstance(result, Counter)


# ══════════════════════════════════════════════════════════════════════════════
#  compute_idf
# ══════════════════════════════════════════════════════════════════════════════

class TestComputeIDF:

    def test_rare_term_has_higher_idf(self, engine):
        docs = [
            ["child", "health", "vaccine"],
            ["child", "nutrition"],
            ["child", "education"],
        ]
        idf = engine.compute_idf(docs)
        # "vaccine" only in 1/3 docs → higher IDF than "child" (3/3 docs)
        assert idf["vaccine"] > idf["child"]

    def test_all_idf_values_positive(self, engine):
        docs = [["cat", "dog"], ["cat", "bird"], ["fish"]]
        idf = engine.compute_idf(docs)
        assert all(v > 0 for v in idf.values())

    def test_single_document_idf_computed(self, engine):
        docs = [["unicef", "children"]]
        idf = engine.compute_idf(docs)
        assert "unicef" in idf and "children" in idf

    def test_returns_dict(self, engine):
        docs = [["child"], ["health"]]
        assert isinstance(engine.compute_idf(docs), dict)

    def test_idf_uses_smoothing(self, engine):
        """IDF = log(N / (df + 1)) + 1 — result must be > 0 even for universal terms."""
        docs = [["child"], ["child"], ["child"]]
        idf = engine.compute_idf(docs)
        assert idf["child"] > 0


# ══════════════════════════════════════════════════════════════════════════════
#  TF-IDF scoring: title boost
# ══════════════════════════════════════════════════════════════════════════════

class TestTFIDFScoring:

    def test_title_boost_increases_score(self, engine):
        """
        A doc whose title contains the query terms should outscore
        a doc where the same terms appear only in the body.
        """
        title_tokens = engine.preprocess_text(
            "vaccination children vaccination children vaccine"
        )
        body_tokens = engine.preprocess_text(
            "nutrition toddler brain development vaccine diet"
        )

        all_docs = [title_tokens, body_tokens]
        idf = engine.compute_idf(all_docs)
        query_tokens = engine.preprocess_text("vaccination children")

        title_set = set(engine.preprocess_text("vaccination children"))
        body_title_set = set(engine.preprocess_text("nutrition toddler"))

        def score(tokens, title_set_local):
            tf = engine.compute_tf(tokens)
            s = 0.0
            for t in query_tokens:
                if t in tf and t in idf:
                    boost = 2.0 if t in title_set_local else 1.0
                    s += tf[t] * idf[t] * boost
            return s

        assert score(title_tokens, title_set) >= score(body_tokens, body_title_set)

    def test_no_query_overlap_gives_zero_score(self, engine):
        """A doc with zero query-term overlap must score exactly 0."""
        doc_tokens = engine.preprocess_text("nutrition toddler brain diet")
        all_docs   = [doc_tokens, engine.preprocess_text("child vaccine health")]
        idf        = engine.compute_idf(all_docs)
        query_tokens = engine.preprocess_text("vaccination children")

        tf = engine.compute_tf(doc_tokens)
        score = sum(
            tf[t] * idf[t]
            for t in query_tokens
            if t in tf and t in idf
        )
        assert score == pytest.approx(0.0)