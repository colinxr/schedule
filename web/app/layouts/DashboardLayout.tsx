'use client';

import { ReactNode } from 'react';
import Navbar from '../components/dashboard/Navbar';
import Header from '../components/dashboard/Header';

interface DashboardLayoutProps {
  children: ReactNode;
}

export default function DashboardLayout({ children }: DashboardLayoutProps) {
  return (
    <div className="min-h-screen bg-background flex w-full">
      <Navbar />

      <div className="flex-1">
        <Header className="sticky top-0 z-50" />
        <main>
          {children}
        </main>
      </div>
    </div>
  );
} 