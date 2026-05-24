# -*- coding: utf-8 -*-
"""
Child Care & Parenting Advice News Extractor
Sources (all free, public RSS, no API key needed):
  1. Zero to Three       – child development ages 0–3 (official nonprofit)
  2. Child & Family Blog – research-backed child development articles
  3. CDC Child Dev.      – CDC tips on child development milestones
  4. HealthyChildren.org – American Academy of Pediatrics (AAP)
  5. Child Dev. Institute – expert parenting articles (feedburner)
  6. Parents Magazine    – broad child care & parenting advice
  7. Child Welfare Monitor – child welfare & awareness articles
  8. Childhood 101       – playful learning & child development
"""

import requests
from bs4 import BeautifulSoup
import mysql.connector
import xml.etree.ElementTree as ET
from datetime import datetime
from email.utils import parsedate_to_datetime
from html import unescape
import sys

# ── All trusted, public RSS feeds ────────────────────────────────────────────
RSS_SOURCES = [
    {
        "name": "Zero to Three",
        "url": "https://www.zerotothree.org/feed/",
        "desc": "Official nonprofit — child development ages 0-3"
    },
    {
        "name": "Child & Family Blog",
        "url": "https://childandfamilyblog.com/feed/",
        "desc": "Research-backed child development & parenting"
    },
    {
        "name": "CDC – Child Development",
        "url": "https://tools.cdc.gov/api/v2/resources/media/316422.rss",
        "desc": "CDC child development milestones & tips"
    },
    {
        "name": "HealthyChildren (AAP)",
        "url": "https://www.healthychildren.org/English/RSS/Pages/default.aspx",
        "desc": "American Academy of Pediatrics advice"
    },
    {
        "name": "Child Development Info",
        "url": "https://feeds.feedburner.com/childdevelopmentinfo",
        "desc": "Child Development Institute expert articles"
    },
    {
        "name": "Parents Magazine",
        "url": "https://www.parents.com/feed/",
        "desc": "Child care, health & parenting tips"
    },
    {
        "name": "Child Welfare Monitor",
        "url": "https://childwelfaremonitor.org/feed",
        "desc": "Child welfare & awareness articles"
    },
    {
        "name": "Childhood 101",
        "url": "https://childhood101.com/feed",
        "desc": "Playful learning & child development activities"
    },
]

HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/120.0.0.0 Safari/537.36"
    ),
    "Accept": "application/rss+xml, application/xml, text/xml, */*",
}


class ChildCareNewsExtractor:
    def __init__(self):
        self.db_config = {
            'host': 'localhost',
            'port': 3306,
            'user': 'root',
            'password': '',
            'database': 'unicef_news_db'
        }

    # ─────────────────────────────────────────────────────────────
    # MAIN ENTRY POINT
    # ─────────────────────────────────────────────────────────────
    def extract_news(self, num_posts=50):
        """Try every RSS source and collect articles until we have enough."""
        all_articles = []
        failed_sources = []

        for source in RSS_SOURCES:
            if len(all_articles) >= num_posts:
                break
            try:
                articles = self._fetch_rss(source)
                if articles:
                    print(f"   OK  {source['name']}: {len(articles)} articles")
                    all_articles.extend(articles)
                else:
                    print(f"   --  {source['name']}: no items found")
                    failed_sources.append(source['name'])
            except Exception as e:
                print(f"   ERR {source['name']}: {str(e)[:70]}")
                failed_sources.append(source['name'])

        if not all_articles:
            print("\nAll live sources failed. Using built-in sample data.")
            return self.get_sample_news()[:num_posts]

        # Deduplicate by title
        seen = set()
        unique = []
        for art in all_articles:
            key = art['title'].lower().strip()
            if key not in seen:
                seen.add(key)
                unique.append(art)

        print(f"\nTotal unique articles collected: {len(unique)}")
        if failed_sources:
            print(f"Sources that failed: {', '.join(failed_sources)}")

        return unique[:num_posts]

    # ─────────────────────────────────────────────────────────────
    # RSS PARSER (handles both RSS 2.0 and Atom)
    # ─────────────────────────────────────────────────────────────
    def _fetch_rss(self, source):
        """Fetch and parse an RSS or Atom feed. Returns list of article dicts."""
        response = requests.get(source['url'], headers=HEADERS, timeout=12)
        response.raise_for_status()

        root = ET.fromstring(response.content)
        ns = {'atom': 'http://www.w3.org/2005/Atom'}
        articles = []

        # ── RSS 2.0 format ──────────────────────────────────────
        items = root.findall('.//item')
        if items:
            for item in items:
                title = self._text(item, 'title')
                content = (
                    self._text(item, 'description') or
                    self._text(item, '{http://purl.org/rss/1.0/modules/content/}encoded') or
                    title
                )
                url = self._text(item, 'link') or source['url']
                pub_date = self._text(item, 'pubDate') or ''
                category = self._text(item, 'category') or ''

                publish_date = self._parse_date(pub_date)
                content = self._clean_html(content)

                if title and len(content) >= 20:
                    articles.append({
                        'title': title[:250],
                        'content': content[:1500],
                        'url': url,
                        'publish_date': publish_date,
                        'source': source['name'],
                        'category': category[:100],
                    })

        # ── Atom format ─────────────────────────────────────────
        else:
            entries = root.findall('atom:entry', ns)
            for entry in entries:
                title_el = entry.find('atom:title', ns)
                title = (title_el.text or '').strip() if title_el is not None else ''

                content_el = entry.find('atom:content', ns)
                summary_el = entry.find('atom:summary', ns)
                content = ''
                if content_el is not None:
                    content = content_el.text or ''
                elif summary_el is not None:
                    content = summary_el.text or ''

                link_el = entry.find('atom:link', ns)
                url = link_el.get('href', source['url']) if link_el is not None else source['url']

                updated_el = entry.find('atom:updated', ns)
                pub_date = (updated_el.text or '') if updated_el is not None else ''
                publish_date = self._parse_date(pub_date)
                content = self._clean_html(content)

                if title and len(content) >= 20:
                    articles.append({
                        'title': title[:250],
                        'content': content[:1500],
                        'url': url,
                        'publish_date': publish_date,
                        'source': source['name'],
                        'category': '',
                    })

        return articles

    # ─────────────────────────────────────────────────────────────
    # HELPERS
    # ─────────────────────────────────────────────────────────────
    def _text(self, element, tag):
        child = element.find(tag)
        return (child.text or '').strip() if child is not None else ''

    def _clean_html(self, text):
        text = unescape(text or '')
        soup = BeautifulSoup(text, 'html.parser')
        return soup.get_text(separator=' ', strip=True)

    def _parse_date(self, date_str):
        if not date_str:
            return datetime.now().strftime('%Y-%m-%d')
        try:
            return parsedate_to_datetime(date_str).strftime('%Y-%m-%d')
        except Exception:
            pass
        try:
            return date_str[:10]
        except Exception:
            return datetime.now().strftime('%Y-%m-%d')

    # ─────────────────────────────────────────────────────────────
    # FALLBACK SAMPLE DATA
    # ─────────────────────────────────────────────────────────────
    def get_sample_news(self):
        return [
            {
                'title': '10 Ways to Support Your Toddler\'s Brain Development',
                'content': 'The first three years of life are critical for brain development. Talking, singing, reading, and playing with your child every day helps build strong neural connections. Responsive parenting reacting to your baby\'s cues is the single most important thing you can do.',
                'url': 'https://www.zerotothree.org/brain-development',
                'publish_date': datetime.now().strftime('%Y-%m-%d'),
                'source': 'Zero to Three (sample)',
                'category': 'Child Development',
            },
            {
                'title': 'Developmental Milestones: What to Expect at Age 2',
                'content': 'By age 2, most children can say 50 or more words, follow two-step instructions, and begin pretend play. If your child is not meeting these milestones, talk to your pediatrician. Early intervention makes a big difference.',
                'url': 'https://www.cdc.gov/child-development/milestones',
                'publish_date': datetime.now().strftime('%Y-%m-%d'),
                'source': 'CDC (sample)',
                'category': 'Milestones',
            },
            {
                'title': 'Screen Time Guidelines for Children Under 5',
                'content': 'The American Academy of Pediatrics recommends no screen time for children under 18 months except video chatting. For children 2-5, limit screen use to 1 hour per day of high-quality programming. Co-viewing with parents helps children learn from media.',
                'url': 'https://www.healthychildren.org/screentime',
                'publish_date': datetime.now().strftime('%Y-%m-%d'),
                'source': 'HealthyChildren.org AAP (sample)',
                'category': 'Health & Safety',
            },
            {
                'title': 'How to Raise an Emotionally Intelligent Child',
                'content': 'Emotional intelligence starts at home. Name your child\'s feelings out loud: You seem frustrated. Help them problem-solve rather than solving for them. Model healthy emotional expression yourself, children learn by watching.',
                'url': 'https://childandfamilyblog.com/emotional-intelligence',
                'publish_date': datetime.now().strftime('%Y-%m-%d'),
                'source': 'Child & Family Blog (sample)',
                'category': 'Emotional Development',
            },
            {
                'title': 'The Importance of Outdoor Play for Child Development',
                'content': 'Children who spend time outdoors daily show improved concentration, lower stress levels, and better physical health. Unstructured outdoor play encourages creativity, problem-solving, and social skills. Aim for at least 60 minutes of active outdoor time each day.',
                'url': 'https://childhood101.com/outdoor-play',
                'publish_date': datetime.now().strftime('%Y-%m-%d'),
                'source': 'Childhood 101 (sample)',
                'category': 'Play & Learning',
            },
        ]

    # ─────────────────────────────────────────────────────────────
    # DATABASE
    # ─────────────────────────────────────────────────────────────
    def save_to_database(self, news_items):
        connection = None
        cursor = None
        try:
            connection = mysql.connector.connect(**self.db_config)
            cursor = connection.cursor()

            saved_count = 0
            for news in news_items:
                cursor.execute(
                    "SELECT id FROM unicef_news WHERE title = %s",
                    (news['title'],)
                )
                if not cursor.fetchone():
                    cursor.execute(
                        """INSERT INTO unicef_news
                           (title, content, url, publish_date, is_indexed)
                           VALUES (%s, %s, %s, %s, %s)""",
                        (
                            news['title'],
                            news['content'],
                            news['url'],
                            news['publish_date'],
                            False
                        )
                    )
                    saved_count += 1

            connection.commit()
            print(f"Saved {saved_count} new articles to database.")

        except mysql.connector.Error as err:
            print(f"Database Error: {err}")
            print("Check XAMPP is running and 'unicef_news_db' exists on port 3306.")

        finally:
            if connection and connection.is_connected():
                if cursor:
                    cursor.close()
                connection.close()


# ─────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    extractor = ChildCareNewsExtractor()

    num_posts = int(sys.argv[1]) if len(sys.argv) > 1 else 20
    print(f"Fetching {num_posts} child care & parenting articles...\n")

    news = extractor.extract_news(num_posts=num_posts)

    print(f"\nPreview:")
    print("-" * 70)
    for i, item in enumerate(news, 1):
        print(f"{i:02d}. [{item['source']}] {item['publish_date']}")
        print(f"    {item['title'][:80]}")
        print()

    extractor.save_to_database(news)