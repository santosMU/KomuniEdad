import { NextResponse } from "next/server";
import { createSupabaseServerClient } from "@/lib/supabase/server";

export const dynamic = "force-dynamic";

export async function GET() {
  try {
    const supabase = createSupabaseServerClient();

    const { data, error } = await supabase.rpc("health_check");

    if (error) {
      return NextResponse.json(
        {
          status: "error",
          database: {
            status: "unavailable",
            message: error.message,
          },
        },
        { status: 503 }
      );
    }

    return NextResponse.json({
      status: "ok",
      database: data,
    });
  } catch (error) {
    const message =
      error instanceof Error ? error.message : "Unknown database health error";

    return NextResponse.json(
      {
        status: "error",
        database: {
          status: "unavailable",
          message,
        },
      },
      { status: 503 }
    );
  }
}
