const RESEND_URL = "https://api.resend.com/emails";
const FROM = "Aquilizan Dental Clinic <appointments@aquilizan.com>";
const MAX_BODY = 8 * 1024 * 1024;

const json = (body: unknown, status = 200) => new Response(JSON.stringify(body), {
  status,
  headers: { "content-type": "application/json", "cache-control": "no-store" },
});

Deno.serve(async (request) => {
  if (request.method !== "POST") return json({ error: "Method not allowed" }, 405);
  if (Number(request.headers.get("content-length") ?? 0) > MAX_BODY) return json({ error: "Payload too large" }, 413);

  // Supabase verifies the signature before invocation. This additional claim check
  // prevents ordinary signed-in users from turning the function into an email relay.
  const token = request.headers.get("authorization")?.replace(/^Bearer\s+/i, "") ?? "";
  try {
    const part = token.split(".")[1].replace(/-/g, "+").replace(/_/g, "/");
    const payload = JSON.parse(atob(part.padEnd(Math.ceil(part.length / 4) * 4, "=")));
    if (payload.role !== "service_role") return json({ error: "Forbidden" }, 403);
  } catch {
    return json({ error: "Unauthorized" }, 401);
  }

  let body: Record<string, unknown>;
  try { body = await request.json(); } catch { return json({ error: "Invalid JSON" }, 422); }

  const to = typeof body.to === "string" ? body.to.trim() : "";
  const subject = typeof body.subject === "string" ? body.subject.trim() : "";
  const html = typeof body.html === "string" ? body.html : "";
  const text = typeof body.text === "string" ? body.text : "";
  const idempotencyKey = typeof body.idempotency_key === "string" ? body.idempotency_key : "";
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(to) || !subject || subject.length > 200 || !html || !text || !idempotencyKey || idempotencyKey.length > 255) {
    return json({ error: "Invalid email payload" }, 422);
  }

  const attachments = Array.isArray(body.attachments) ? body.attachments : [];
  if (attachments.length > 3 || attachments.some((a) => !a || typeof a.filename !== "string" || !a.filename.toLowerCase().endsWith(".pdf") || typeof a.content !== "string")) {
    return json({ error: "Invalid attachments" }, 422);
  }

  const resendKey = Deno.env.get("RESEND_API_KEY");
  if (!resendKey) return json({ error: "Email service is not configured" }, 503);

  const outbound: Record<string, unknown> = { from: FROM, to: [to], subject, html, text, attachments };
  if (typeof body.reply_to === "string" && emailPattern.test(body.reply_to)) outbound.reply_to = [body.reply_to];

  const response = await fetch(RESEND_URL, {
    method: "POST",
    headers: { authorization: `Bearer ${resendKey}`, "content-type": "application/json", "idempotency-key": idempotencyKey },
    body: JSON.stringify(outbound),
  });
  if (!response.ok) return json({ error: "Provider rejected the message" }, 502);
  const result = await response.json();
  return typeof result.id === "string" ? json({ id: result.id }) : json({ error: "Invalid provider response" }, 502);
});
