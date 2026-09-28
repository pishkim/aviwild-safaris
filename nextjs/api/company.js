export default async function handler(req, res) {
  try {
    const phpRes = await fetch(`${process.env.BLOG_API_URL}/company.php`, {
      headers: { 'X-API-Key': process.env.BLOG_API_KEY },
      next: { revalidate: 60 },
    });
    const data = await phpRes.json();
    res.status(phpRes.status).json(data);
  } catch (err) {
    res.status(500).json({ success: false, error: 'Upstream fetch failed' });
  }
}