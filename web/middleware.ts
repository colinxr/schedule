import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

export function middleware(request: NextRequest) {
  const token = request.cookies.get('session_token');

//   // Check if the user is trying to access dashboard routes
//   if (request.nextUrl.pathname.startsWith('/a')) {
//     if (!token) {
//       return NextResponse.redirect(new URL('/auth/login', request.url));
//     }
//   }

//   // Check if the user is trying to access auth pages while logged in
//   if (request.nextUrl.pathname.startsWith('/auth')) {
//     if (token) {
//       return NextResponse.redirect(new URL('/a', request.url));
//     }
//   }

  return NextResponse.next();
}

export const config = {
  matcher: ['/a/:path*', '/auth/:path*'],
}; 