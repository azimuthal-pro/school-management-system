import { NextRequest, NextResponse } from 'next/server';

// Note: This middleware works with server-side cookies only.
// Client-side auth (localStorage) is handled by AuthContext.tsx
export function middleware(request: NextRequest) {
  // Check for cookie-based auth (if implemented) or skip for localStorage-based auth
  const token = request.cookies.get('authToken');

  // For now, rely on client-side auth redirect in AuthContext
  // This middleware can be enhanced when backend moves to httpOnly cookies
  return NextResponse.next();
}

export const config = {
  matcher: [],
};
