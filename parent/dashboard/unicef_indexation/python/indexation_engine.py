
        # -*- coding: utf-8 -*-
import warnings
import sys
import os

# Suppress all warnings to ensure clean JSON output
warnings.filterwarnings('ignore')

# Suppress print statements to stdout except for final JSON
import logging
logging.disable(logging.CRITICAL)

import mysql.connector
import re
from collections import Counter
import json
import math
from datetime import date, datetime

class DateTimeEncoder(json.JSONEncoder):
    """Custom JSON encoder for datetime and date objects"""
    def default(self, o):
        if isinstance(o, (date, datetime)):
            return o.isoformat()
        return super().default(o)

class IndexationEngine:
    def __init__(self):
        self.db_config = {
            'host': 'localhost',
            'port': 3306,           # Changed from default 3306 to 3307
            'user': 'root',
            'password': '',  # Empty for XAMPP default
            'database': 'unicef_news_db'
        }

    def ensure_tables(self, cursor):
        """
        FIX Bug 1 & 2: Create missing DB objects so the engine never crashes
        on a fresh database.
          - Adds 'indexed_date' column to unicef_news if it doesn't exist.
          - Creates 'indexation_log' table if it doesn't exist.
        """
        # Add indexed_date column to unicef_news if missing
        cursor.execute("SHOW COLUMNS FROM unicef_news LIKE 'indexed_date'")
        if not cursor.fetchone():
            cursor.execute(
                "ALTER TABLE unicef_news ADD COLUMN indexed_date DATETIME NULL DEFAULT NULL"
            )

        # Create indexation_log table if missing
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS indexation_log (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                query_text    TEXT NOT NULL,
                matched_docs  TEXT,
                indexed_count INT DEFAULT 0,
                searched_at   DATETIME DEFAULT NOW()
            )
        """)
    
    def preprocess_text(self, text):
        """Text preprocessing: lowercase, remove punctuation, tokenize"""
        text = text.lower()
        text = re.sub(r'[^a-zA-Z\s]', '', text)
        tokens = text.split()
        stopwords = {'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 
                    'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
                    'has', 'have', 'had', 'that', 'this', 'these', 'those'}
        tokens = [token for token in tokens if token not in stopwords and len(token) > 2]
        return tokens
    
    def compute_tf(self, tokens):
        """Compute Term Frequency"""
        tf = Counter(tokens)
        total_terms = len(tokens)
        if total_terms > 0:
            for term in tf:
                tf[term] = tf[term] / total_terms
        return tf
    
    def compute_idf(self, all_documents_tokens):
        """Compute Inverse Document Frequency"""
        total_docs = len(all_documents_tokens)
        term_doc_count = Counter()
        
        for doc_tokens in all_documents_tokens:
            unique_terms = set(doc_tokens)
            for term in unique_terms:
                term_doc_count[term] += 1
        
        idf = {}
        for term, doc_count in term_doc_count.items():
            idf[term] = math.log(total_docs / (doc_count + 1)) + 1  # Added smoothing
        
        return idf
    
    def search_and_index(self, query):
        """Main search and indexation function"""
        connection = None  # FIX Bug 5: declare outside try so finally can close it
        cursor = None
        try:
            connection = mysql.connector.connect(**self.db_config)
            cursor = connection.cursor(dictionary=True)

            # FIX Bug 1 & 2: ensure required columns and tables exist
            self.ensure_tables(cursor)
            connection.commit()

            # Fetch all documents
            cursor.execute("SELECT id, title, content, is_indexed FROM unicef_news")
            documents = cursor.fetchall()

            if not documents:
                return {
                    "results": [],
                    "indexed_count": 0,
                    "query": query,
                    "total_matches": 0
                }

            # Preprocess all documents
            all_tokens = []
            title_tokens_list = []   # kept separate for title boost
            doc_ids = []
            doc_titles = []

            for doc in documents:
                full_text = f"{doc['title']} {doc['content']}"
                tokens = self.preprocess_text(full_text)
                title_tokens = self.preprocess_text(doc['title'])
                all_tokens.append(tokens)
                title_tokens_list.append(set(title_tokens))
                doc_ids.append(doc['id'])
                doc_titles.append(doc['title'])

            # FIX Bug 4: detect empty query early and return a clear error
            query_tokens = self.preprocess_text(query)
            if not query_tokens:
                return {
                    "error": "Query contains only stopwords or is too short. Please use more specific terms.",
                    "results": [],
                    "indexed_count": 0,
                    "query": query,
                    "total_matches": 0
                }

            # Compute IDF across all documents
            idf_scores = self.compute_idf(all_tokens)

            # FIX Bug 3: correct TF-IDF scoring
            # Old (wrong): score += doc_tf[t] * idf[t] * query_tf[t]
            #   → query_tf is 1/N per term, so multi-word queries get shrunk scores
            # Fixed: score = sum(doc_tf[t] * idf[t]) for each query term
            #        + 2× title boost when the term appears in the title
            scores = []
            for idx, doc_tokens in enumerate(all_tokens):
                doc_tf = self.compute_tf(doc_tokens)
                score = 0.0

                for term in query_tokens:
                    if term in doc_tf and term in idf_scores:
                        tfidf = doc_tf[term] * idf_scores[term]
                        # Title boost: term in title counts double
                        boost = 2.0 if term in title_tokens_list[idx] else 1.0
                        score += tfidf * boost

                if score > 0:
                    scores.append((doc_ids[idx], score, doc_titles[idx]))

            # Sort by score descending
            scores.sort(key=lambda x: x[1], reverse=True)

            # Update is_indexed flag for relevant documents
            indexed_count = 0
            for doc_id, score, title in scores[:10]:
                cursor.execute("""
                    UPDATE unicef_news
                    SET is_indexed = TRUE, indexed_date = NOW()
                    WHERE id = %s AND is_indexed = FALSE
                """, (doc_id,))
                if cursor.rowcount > 0:
                    indexed_count += 1

            # Log the search query
            matched_docs_json = json.dumps([doc_id for doc_id, _, _ in scores[:10]])
            cursor.execute("""
                INSERT INTO indexation_log (query_text, matched_docs, indexed_count)
                VALUES (%s, %s, %s)
            """, (query, matched_docs_json, indexed_count))

            connection.commit()

            # Fetch full details of top results
            if scores:
                doc_ids_list = [doc_id for doc_id, _, _ in scores[:10]]
                format_strings = ','.join(['%s'] * len(doc_ids_list))
                cursor.execute(
                    f"SELECT * FROM unicef_news "
                    f"WHERE id IN ({format_strings}) "
                    f"ORDER BY FIELD(id, {format_strings})",
                    doc_ids_list + doc_ids_list
                )
                results = cursor.fetchall()
            else:
                results = []

            return {
                "results": results,
                "indexed_count": indexed_count,
                "query": query,
                "total_matches": len(scores)
            }

        except mysql.connector.Error as db_err:
            raise Exception(f"Database error: {str(db_err)}")
        except Exception as e:
            raise Exception(f"Search error: {str(e)}")
        finally:
            # FIX Bug 5: always close connection, even if an exception was raised
            if cursor is not None:
                cursor.close()
            if connection is not None and connection.is_connected():
                connection.close()

if __name__ == "__main__":
    # Handle help flags
    if len(sys.argv) > 1 and sys.argv[1] in ['--help', '-h', '--version', '-v']:
        print("UNICEF News Indexation Engine")
        print("Usage: python indexation_engine.py <search_query>")
        print("Example: python indexation_engine.py 'children vaccination'")
        sys.exit(0)
    
    if len(sys.argv) > 1:
        try:
            query = sys.argv[1]
            engine = IndexationEngine()
            result = engine.search_and_index(query)
            print(json.dumps(result, cls=DateTimeEncoder))
        except mysql.connector.Error as db_error:
            # Database error
            print(json.dumps({
                "error": f"Database error: {str(db_error)}",
                "results": [],
                "indexed_count": 0,
                "total_matches": 0
            }))
            sys.exit(1)
        except Exception as e:
            # General error
            print(json.dumps({
                "error": f"Error: {str(e)}",
                "results": [],
                "indexed_count": 0,
                "total_matches": 0
            }))
            sys.exit(1)
    else:
        print(json.dumps({"error": "No query provided", "results": [], "indexed_count": 0, "total_matches": 0}))