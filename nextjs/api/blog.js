export default async function handler(req, res) {
  const { id, status, search, page = 1, limit = 10 } = req.query;

  const params = new URLSearchParams();
  if (id)     params.set('id', id);
  if (status) params.set('status', status);
  if (search) params.set('search', search);
  params.set('page', page);
  params.set('limit', limit);

  try {
    const phpRes = await fetch(
      `${process.env.BLOG_API_URL}/blog.php?${params.toString()}`,
      {
        headers: { 'X-API-Key': process.env.BLOG_API_KEY },
        // cache on the server for 60s
        next: { revalidate: 60 },
      }
    );

    const data = await phpRes.json();
    res.status(phpRes.status).json(data);
  } catch (err) {
    res.status(500).json({ success: false, error: 'Upstream fetch failed' });
  }
}