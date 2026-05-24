# -*- coding: utf-8 -*-
"""Evaluate TF/IDF/TF-IDF and retrieval metrics for the query 'child'.

Usage:
  python evaluate_query_child.py

Outputs JSON to stdout with tf, idf, tfidf per document and precision/recall/F1 and precision@5.
"""
import os
import sys
import json
from collections import defaultdict

# ensure local module imports work
HERE = os.path.dirname(__file__)
sys.path.insert(0, HERE)

import mysql.connector
from indexation_engine import IndexationEngine

QUERY_TERM = 'child'

def main():
    engine = IndexationEngine()
    cfg = engine.db_config

    conn = None
    cursor = None
    try:
        conn = mysql.connector.connect(**cfg)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, title, content FROM unicef_news")
        docs = cursor.fetchall()

        if not docs:
            print(json.dumps({"error": "No documents found in unicef_news"}))
            return

        # preprocess and compute tf per doc
        doc_tokens = []
        doc_tf = {}
        doc_map = {}
        for d in docs:
            doc_id = d['id']
            full = f"{d.get('title','')} {d.get('content','')}"
            tokens = engine.preprocess_text(full)
            doc_tokens.append(tokens)
            tf = engine.compute_tf(tokens)
            doc_tf[doc_id] = tf
            doc_map[doc_id] = {"title": d.get('title',''), "content_len": len(d.get('content',''))}

        # compute idf across all docs
        idf = engine.compute_idf(doc_tokens)

        # compute tf-idf for QUERY_TERM
        tfidf_scores = []
        for d in docs:
            doc_id = d['id']
            tf_val = doc_tf[doc_id].get(QUERY_TERM, 0.0)
            idf_val = idf.get(QUERY_TERM, 0.0)
            tfidf = tf_val * idf_val
            tfidf_scores.append((doc_id, tf_val, idf_val, tfidf))

        # sort by tfidf descending
        tfidf_scores.sort(key=lambda x: x[3], reverse=True)

        # Define relevance: a document is relevant if the preprocessed tokens contain the exact QUERY_TERM
        relevant_ids = {d['id'] for i, d in enumerate(docs) if QUERY_TERM in doc_tokens[i]}
        total_relevant = len(relevant_ids)

        # Consider retrieved set = docs with tfidf>0 (ranked). If none, consider top-k by presence.
        retrieved = [doc_id for doc_id, tf, idf_v, score in tfidf_scores if score > 0]
        # fallback: if no tfidf>0, take top 10 by raw occurrence (i.e., token presence)
        if not retrieved:
            retrieved = [doc_id for doc_id, tf, idf_v, score in tfidf_scores if tf > 0][:10]

        # metrics for retrieved whole set
        tp = sum(1 for rid in retrieved if rid in relevant_ids)
        precision = tp / len(retrieved) if retrieved else 0.0
        recall = tp / total_relevant if total_relevant > 0 else 0.0
        f1 = (2 * precision * recall / (precision + recall)) if (precision + recall) > 0 else 0.0

        # precision@5
        top5 = retrieved[:5]
        tp5 = sum(1 for rid in top5 if rid in relevant_ids)
        precision_at_5 = tp5 / len(top5) if top5 else 0.0

        # prepare output
        per_doc = []
        for doc_id, tf_val, idf_val, tfidf in tfidf_scores:
            per_doc.append({
                "id": doc_id,
                "title": doc_map[doc_id]["title"],
                "tf": tf_val,
                "idf": idf_val,
                "tfidf": tfidf,
                "relevant": doc_id in relevant_ids,
            })

        out = {
            "query": QUERY_TERM,
            "total_documents": len(docs),
            "total_relevant": total_relevant,
            "retrieved_count": len(retrieved),
            "precision": precision,
            "recall": recall,
            "f1": f1,
            "precision_at_5": precision_at_5,
            "ranked_results": per_doc[:50],
        }

        print(json.dumps(out, indent=2))

    except mysql.connector.Error as err:
        print(json.dumps({"error": f"Database error: {err}"}))
    finally:
        if cursor:
            cursor.close()
        if conn and conn.is_connected():
            conn.close()


if __name__ == '__main__':
    main()
