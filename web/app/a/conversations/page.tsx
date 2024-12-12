import ConversationList from '../../components/dashboard/ConversationList';
import { Card } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";

export default function DashboardPage() {
  return (
    <div className="h-[calc(100vh-theme(spacing.16))] flex">
      {/* Left panel - Conversation List */}
      <div className="w-80">
        <ConversationList />
      </div>
      
      <Separator orientation="vertical" />
      
      {/* Right panel - Conversation Body (placeholder for now) */}
      <div className="flex-1 p-6 flex items-center justify-center text-muted-foreground">
        Select a conversation to view messages
      </div>
    </div>
  );
} 