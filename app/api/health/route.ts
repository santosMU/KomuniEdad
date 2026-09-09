import { NextResponse } from "next/server";

export const dynamic = "force-dynamic";

export async function GET() {
  return NextResponse.json({
    status: "ok",
    service: "phase2-backend",
    timestamp: new Date().toISOString(),
  });
}
